<?php

namespace App\Http\Controllers;

use App\Models\HardwareLog;
use App\Models\Pc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PcReportController extends Controller
{
    /**
     * Store or update full PC specifications report from agent.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string|max:100',
            'hostname' => 'required|string|max:100',
            'alias' => 'nullable|string|max:100',
            'stock_status' => 'nullable|string|in:digunakan,bebas',
            'department' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'ip' => 'nullable|string|max:50',
            'mac' => 'nullable|string|max:50',
            'login_user' => 'nullable|string|max:150',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'motherboard' => 'nullable|string|max:150',
            'bios_version' => 'nullable|string|max:100',
            'serial' => 'nullable|string|max:100',
            'os' => 'nullable|string|max:150',
            'cpu' => 'nullable|string|max:150',
            'cpu_cores' => 'nullable|integer',
            'cpu_threads' => 'nullable|integer',
            'gpu' => 'nullable|string|max:150',
            'gpu_details' => 'nullable|array',
            'monitors' => 'nullable|array',
            'ram_gb' => 'nullable|numeric',
            'ram_type' => 'nullable|string|max:20',
            'ram_speed' => 'nullable|integer',
            'ram_details' => 'nullable|array',
            'uptime_h' => 'nullable|numeric',
            'battery' => 'nullable|array',
            'physical_disks' => 'nullable|array',
            'partitions' => 'nullable|array',
        ]);

        $disks = [
            'physical' => $validated['physical_disks'] ?? [],
            'partitions' => $validated['partitions'] ?? [],
        ];
        unset($validated['physical_disks'], $validated['partitions']);

        if (!empty($validated['motherboard'])) {
            $validated['motherboard'] = preg_replace('/\s+/', ' ', trim($validated['motherboard']));
        }
        if (!empty($validated['bios_version'])) {
            $validated['bios_version'] = preg_replace('/\s+/', ' ', trim($validated['bios_version']));
        }
        if (!empty($validated['gpu'])) {
            $validated['gpu'] = preg_replace('/\s+/', ' ', trim($validated['gpu']));
        }

        // Jika PC sudah memiliki alias/departemen/lokasi di web, jangan hapus jika agent kirim null
        $existingPc = Pc::where('uid', $validated['uid'])->first();
        if ($existingPc) {
            // Deteksi perubahan hardware & catat ke riwayat audit trail
            $this->detectAndLogHardwareChanges($existingPc, $validated, $disks);

            if (!empty($existingPc->alias) && empty($validated['alias'])) {
                unset($validated['alias']);
            }
            if (!empty($existingPc->stock_status) && empty($validated['stock_status'])) {
                unset($validated['stock_status']);
            }
            if (!empty($existingPc->department) && empty($validated['department'])) {
                unset($validated['department']);
            }
            if (!empty($existingPc->location) && empty($validated['location'])) {
                unset($validated['location']);
            }
        }

        Pc::updateOrCreate(
            ['uid' => $validated['uid']],
            $validated + [
                'disks' => $disks,
                'last_report_at' => now(),
                'last_poll_at' => now(),
                'sync_requested_at' => null,
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Handle periodic polling from agent to check sync request and update online heartbeat.
     */
    public function poll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uid' => 'required|string|max:100',
            'hostname' => 'nullable|string|max:100',
        ]);

        $pc = Pc::where('uid', $validated['uid'])->first();

        if ($pc === null) {
            return response()->json(['sync' => true]);
        }

        $pc->updateQuietly(['last_poll_at' => now()]);

        return response()->json(['sync' => $pc->sync_requested_at !== null]);
    }

    /**
     * Compare previous hardware specs with newly reported specs and log any changes.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $newDisks
     */
    private function detectAndLogHardwareChanges(Pc $pc, array $validated, array $newDisks): void
    {
        // 1. Cek Perubahan RAM
        if (!empty($validated['ram_gb']) && (float) $pc->ram_gb > 0) {
            $oldRam = (float) $pc->ram_gb;
            $newRam = (float) $validated['ram_gb'];

            if (abs($oldRam - $newRam) >= 0.5) {
                $isDowngrade = $newRam < $oldRam;
                HardwareLog::create([
                    'pc_id' => $pc->id,
                    'component' => 'RAM',
                    'change_type' => $isDowngrade ? 'downgraded' : 'upgraded',
                    'description' => $isDowngrade
                        ? "Kapasitas RAM berkurang dari {$oldRam} GB menjadi {$newRam} GB (kemungkinan keping RAM dicabut/rusak)"
                        : "Kapasitas RAM bertambah dari {$oldRam} GB menjadi {$newRam} GB",
                    'old_value' => "{$oldRam} GB",
                    'new_value' => "{$newRam} GB",
                ]);
            }
        }

        // 2. Cek Perubahan CPU
        if (!empty($validated['cpu']) && !empty($pc->cpu) && $pc->cpu !== $validated['cpu']) {
            HardwareLog::create([
                'pc_id' => $pc->id,
                'component' => 'CPU',
                'change_type' => 'replaced',
                'description' => "Processor diganti dari '{$pc->cpu}' menjadi '{$validated['cpu']}'",
                'old_value' => $pc->cpu,
                'new_value' => $validated['cpu'],
            ]);
        }

        // 3. Cek Perubahan GPU
        if (!empty($validated['gpu']) && !empty($pc->gpu) && $pc->gpu !== $validated['gpu']) {
            HardwareLog::create([
                'pc_id' => $pc->id,
                'component' => 'GPU',
                'change_type' => 'replaced',
                'description' => "Kartu Grafis (GPU) diganti dari '{$pc->gpu}' menjadi '{$validated['gpu']}'",
                'old_value' => $pc->gpu,
                'new_value' => $validated['gpu'],
            ]);
        } elseif (!empty($pc->gpu) && empty($validated['gpu'])) {
            HardwareLog::create([
                'pc_id' => $pc->id,
                'component' => 'GPU',
                'change_type' => 'removed',
                'description' => "Kartu Grafis '{$pc->gpu}' tidak lagi terdeteksi / dilepas",
                'old_value' => $pc->gpu,
                'new_value' => null,
            ]);
        }

        // 4. Cek Perubahan Motherboard
        if (!empty($validated['motherboard']) && !empty($pc->motherboard) && $pc->motherboard !== $validated['motherboard']) {
            HardwareLog::create([
                'pc_id' => $pc->id,
                'component' => 'Motherboard',
                'change_type' => 'replaced',
                'description' => "Motherboard berubah dari '{$pc->motherboard}' menjadi '{$validated['motherboard']}'",
                'old_value' => $pc->motherboard,
                'new_value' => $validated['motherboard'],
            ]);
        }

        // 5. Cek Perubahan Disk Fisik (Storage)
        $oldDisks = $pc->disks['physical'] ?? [];
        $incomingDisks = $newDisks['physical'] ?? [];

        if (!empty($oldDisks) && !empty($incomingDisks)) {
            $oldModels = array_map(fn($d) => $d['model'] ?? '', $oldDisks);
            $newModels = array_map(fn($d) => $d['model'] ?? '', $incomingDisks);

            // Disk yang hilang
            foreach ($oldDisks as $d) {
                $m = $d['model'] ?? '';
                if ($m && !in_array($m, $newModels, true)) {
                    HardwareLog::create([
                        'pc_id' => $pc->id,
                        'component' => 'Storage',
                        'change_type' => 'removed',
                        'description' => "Storage fisik '{$m}' ({$d['size_gb']} GB, {$d['type']}) dilepas atau tidak terdeteksi",
                        'old_value' => $m,
                        'new_value' => null,
                    ]);
                }
            }

            // Disk baru
            foreach ($incomingDisks as $d) {
                $m = $d['model'] ?? '';
                if ($m && !in_array($m, $oldModels, true)) {
                    HardwareLog::create([
                        'pc_id' => $pc->id,
                        'component' => 'Storage',
                        'change_type' => 'added',
                        'description' => "Storage fisik baru '{$m}' ({$d['size_gb']} GB, {$d['type']}) ditambahkan",
                        'old_value' => null,
                        'new_value' => $m,
                    ]);
                }
            }
        }
    }
}
