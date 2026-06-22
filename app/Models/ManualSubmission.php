<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualSubmission extends Model
{
    protected $fillable = [
        'no_induk',
        'nama',
        'pai_module_id',
        'note',
    ];

    public function paiModule(): BelongsTo
    {
        return $this->belongsTo(PaiModule::class);
    }
}
