<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleCourse extends Model
{
    protected $table = 'module_course';

    protected $fillable = [
        'pai_module_id',
        'course_id',
        'curriculum',
    ];

    public function paiModule(): BelongsTo
    {
        return $this->belongsTo(PaiModule::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
