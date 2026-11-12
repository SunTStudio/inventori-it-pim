<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardwareLog extends Model
{
    protected $guarded = [];

    /**
     * Get the PC that owns this hardware log.
     */
    public function pc(): BelongsTo
    {
        return $this->belongsTo(Pc::class);
    }
}

