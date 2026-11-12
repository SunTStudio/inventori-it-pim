<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pc extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'disks' => 'array',
        'ram_details' => 'array',
        'ram_speed' => 'integer',
        'gpu_details' => 'array',
        'monitors' => 'array',
        'battery' => 'array',
        'last_report_at' => 'datetime',
        'last_poll_at' => 'datetime',
        'sync_requested_at' => 'datetime',
    ];

    /**
     * Get display name (alias if available, otherwise hostname).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->alias ?: $this->hostname;
    }

    /**
     * Check whether the PC is considered online (polled within last 3 minutes).
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_poll_at !== null && $this->last_poll_at->gt(now()->subMinutes(3));
    }

    /**
     * Check whether there is an unresolved synchronization request.
     */
    public function getSyncPendingAttribute(): bool
    {
        return $this->sync_requested_at !== null;
    }

    /**
     * Get stock status human-friendly label.
     */
    public function getStockStatusLabelAttribute(): string
    {
        return $this->stock_status === 'bebas' ? 'Stock Bebas' : 'Stock Digunakan';
    }

    /**
     * Determine whether the PC is free / unassigned stock.
     */
    public function getIsFreeStockAttribute(): bool
    {
        return $this->stock_status === 'bebas';
    }

    /**
     * Get effective UltraViewer password (fallback to default 'pckantor').
     */
    public function getUltraviewerPasswordAttribute(): string
    {
        return $this->ultraviewer_pass ?: 'pckantor';
    }

    /**
     * Get nicely formatted UltraViewer ID (e.g. 13 629 618 2).
     */
    public function getUltraviewerFormattedIdAttribute(): string
    {
        $id = preg_replace('/\s+/', '', (string) $this->ultraviewer_id);
        if ($id === '') {
            return '-';
        }

        // Chunk into groups of 3 or 4 digits
        return trim(chunk_split($id, 3, ' '));
    }

    /**
     * Get hardware change audit logs.
     */
    public function hardwareLogs(): HasMany
    {
        return $this->hasMany(HardwareLog::class)->latest();
    }

    /**
     * Early Warning System: detect low disk space, degraded disk health, or prolonged offline.
     *
     * @return array<int, array{type: string, message: string, severity: string}>
     */
    public function getWarningsAttribute(): array
    {
        $warnings = [];

        // 1. Cek Partisi Drive (terutama C: atau partisi sistem)
        foreach ($this->disks['partitions'] ?? [] as $part) {
            $total = (float) ($part['total_gb'] ?? 0);
            $free = (float) ($part['free_gb'] ?? 0);
            $drive = $part['drive'] ?? 'Drive';

            if ($total > 0) {
                $pctFree = ($free / $total) * 100;
                if ($free <= 15 || $pctFree <= 10) {
                    $warnings[] = [
                        'type' => 'storage_low',
                        'severity' => $free <= 5 ? 'danger' : 'warning',
                        'message' => "Partisi {$drive} menipis: sisa {$free} GB ({round($pctFree)}% kosong)",
                    ];
                }
            }
        }

        // 2. Cek Kesehatan Disk Fisik (SMART Health Status)
        foreach ($this->disks['physical'] ?? [] as $disk) {
            $health = $disk['health'] ?? 'Healthy';
            $model = $disk['model'] ?? 'Disk';
            if (!in_array(strtolower($health), ['healthy', 'ok', ''])) {
                $warnings[] = [
                    'type' => 'disk_health',
                    'severity' => 'danger',
                    'message' => "Kesehatan disk {$model} bermasalah: Status '{$health}'",
                ];
            }
        }

        // 3. Cek Offline Berkepanjangan (> 30 hari)
        if ($this->last_poll_at && $this->last_poll_at->lt(now()->subDays(30))) {
            $days = (int) $this->last_poll_at->diffInDays(now());
            $warnings[] = [
                'type' => 'offline_long',
                'severity' => 'warning',
                'message' => "PC tidak aktif selama {$days} hari (sejak {$this->last_poll_at->format('d/m/Y')})",
            ];
        }

        return $warnings;
    }

    /**
     * Determine whether the PC has active warnings.
     */
    public function getHasWarningsAttribute(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Get recent hardware change within last 7 days.
     */
    public function getRecentHardwareChangeAttribute(): ?HardwareLog
    {
        return $this->hardwareLogs()->where('created_at', '>=', now()->subDays(7))->first();
    }
}
