<?php

namespace App\Http\Controllers;

use App\Models\Pc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcController extends Controller
{
    /**
     * Display the list of registered PCs.
     */
    public function index(): View
    {
        return view('pcs.index', [
            'pcs' => Pc::orderBy('hostname')->get(),
        ]);
    }

    /**
     * Export all PCs data to Excel-compatible CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $pcs = Pc::orderBy('hostname')->get();
        $fileName = 'inventaris_pc_kantor_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($pcs): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Microsoft Excel opens it with proper encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID', 'Status Jaringan', 'Kategori Stock', 'Nama Alias', 'Hostname', 'Divisi', 'Lokasi',
                'IP Address', 'MAC Address', 'User Login', 'Merk / Model', 'Motherboard', 'Serial Number',
                'Processor (CPU)', 'Cores', 'Threads', 'Kartu Grafis (GPU)', 'RAM (GB)', 'Tipe RAM', 'Speed RAM (MHz)', 'Sistem Operasi',
                'Monitor', 'Baterai', 'Uptime (Jam)', 'Terakhir Lapor', 'Catatan',
            ]);

            foreach ($pcs as $p) {
                $monitorNames = !empty($p->monitors)
                    ? implode(', ', array_filter(array_map(fn($m) => trim(($m['model'] ?? '').' '.($m['manufacturer'] ?? '')), $p->monitors)))
                    : '-';
                $batteryText = !empty($p->battery['is_laptop'])
                    ? (($p->battery['percent'] ?? '?').'% ('.($p->battery['status'] ?? 'Laptop').')')
                    : 'Desktop (Tanpa Baterai)';

                fputcsv($handle, [
                    $p->id,
                    $p->is_online ? 'Online' : 'Offline',
                    $p->stock_status_label,
                    $p->alias ?: '-',
                    $p->hostname,
                    $p->department ?: '-',
                    $p->location ?: '-',
                    $p->ip ?: '-',
                    $p->mac ?: '-',
                    $p->login_user ?: '-',
                    trim(($p->manufacturer ?? '').' '.($p->model ?? '')) ?: '-',
                    $p->motherboard ?: '-',
                    $p->serial ?: '-',
                    $p->cpu ?: '-',
                    $p->cpu_cores ?: '-',
                    $p->cpu_threads ?: '-',
                    $p->gpu ?: '-',
                    $p->ram_gb ?: '-',
                    $p->ram_type ?: '-',
                    $p->ram_speed ?: '-',
                    $p->os ?: '-',
                    $monitorNames ?: '-',
                    $batteryText,
                    $p->uptime_h ?: '-',
                    $p->last_report_at ? $p->last_report_at->format('Y-m-d H:i:s') : '-',
                    $p->notes ?: '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Display the comprehensive details of a specific PC.
     */
    public function show(Pc $pc): View
    {
        return view('pcs.show', [
            'pc' => $pc->load(['hardwareLogs']),
        ]);
    }

    /**
     * Display printable asset QR sticker label for the specified PC.
     */
    public function printLabel(Pc $pc): View
    {
        return view('pcs.print-label', [
            'pc' => $pc,
        ]);
    }

    /**
     * Download a Windows Remote Desktop (.rdp) connection file for the PC.
     */
    public function downloadRdp(Pc $pc): StreamedResponse
    {
        $ip = $pc->ip ?: '127.0.0.1';
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $pc->display_name);
        $fileName = "Remote-{$safeName}.rdp";

        $content = "full address:s:{$ip}:3389\r\n"
            ."prompt for credentials:i:1\r\n"
            ."administrative session:i:1\r\n"
            ."screen mode id:i:2\r\n"
            ."use multimon:i:0\r\n"
            ."session bpp:i:32\r\n"
            ."compression:i:1\r\n"
            ."keyboardhook:i:2\r\n"
            ."audiomode:i:0\r\n"
            ."redirectprinters:i:0\r\n"
            ."redirectclipboard:i:1\r\n";

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'application/x-rdp',
        ]);
    }

    /**
     * Download a 1-click Radmin connection launcher (.bat) for the PC.
     */
    public function downloadRadmin(Pc $pc): StreamedResponse
    {
        $ip = $pc->ip ?: '127.0.0.1';
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $pc->display_name);
        $fileName = "Radmin-{$safeName}.bat";

        $content = "@echo off\r\n"
            ."title Menghubungkan ke {$pc->display_name} via Radmin...\r\n"
            ."if exist \"C:\\Program Files (x86)\\Radmin VPN\\Radmin.exe\" (\r\n"
            ."    start \"\" \"C:\\Program Files (x86)\\Radmin VPN\\Radmin.exe\" /connect:{$ip}\r\n"
            .") else if exist \"C:\\Program Files (x86)\\Radmin Viewer 3\\Radmin.exe\" (\r\n"
            ."    start \"\" \"C:\\Program Files (x86)\\Radmin Viewer 3\\Radmin.exe\" /connect:{$ip}\r\n"
            .") else if exist \"C:\\Program Files\\Radmin Viewer 3\\Radmin.exe\" (\r\n"
            ."    start \"\" \"C:\\Program Files\\Radmin Viewer 3\\Radmin.exe\" /connect:{$ip}\r\n"
            .") else (\r\n"
            ."    echo Radmin Viewer tidak ditemukan di Program Files.\r\n"
            ."    echo Silakan buka Radmin Viewer manual dan masukkan IP: {$ip}\r\n"
            ."    pause\r\n"
            .")\r\n";

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'application/x-bat',
        ]);
    }

    /**
     * Update editable metadata for the specified PC (alias, department, location, notes).
     */
    public function update(Request $request, Pc $pc): RedirectResponse
    {
        $validated = $request->validate([
            'alias' => 'nullable|string|max:100',
            'stock_status' => 'nullable|string|in:digunakan,bebas',
            'department' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['stock_status'])) {
            $validated['stock_status'] = 'digunakan';
        }

        $pc->update($validated);

        return back()->with('status', "Informasi alias untuk {$pc->hostname} berhasil diperbarui.");
    }

    /**
     * Remove the specified PC from inventory.
     */
    public function destroy(Pc $pc): RedirectResponse
    {
        $name = $pc->display_name;
        $pc->delete();

        return redirect()->route('pcs.index')->with('status', "Perangkat {$name} berhasil dihapus dari inventaris.");
    }

    /**
     * Request sync for all PCs.
     */
    public function syncAll(): RedirectResponse
    {
        Pc::query()->update(['sync_requested_at' => now()]);

        return back()->with(
            'status',
            'Permintaan sinkronisasi dikirim ke semua PC. PC yang online akan memperbarui data dalam ±30 detik.'
        );
    }

    /**
     * Request sync for a single PC.
     */
    public function sync(Pc $pc): RedirectResponse
    {
        $pc->update(['sync_requested_at' => now()]);

        return back()->with(
            'status',
            "Permintaan sinkronisasi untuk {$pc->display_name} telah dikirim."
        );
    }
}
