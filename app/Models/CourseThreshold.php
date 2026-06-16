<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseThreshold extends Model
{
    protected $fillable = [
        'course_id',
        'percentile',
        'threshold_na',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'percentile' => 'decimal:2',
            'threshold_na' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
