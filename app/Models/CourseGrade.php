<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGrade extends Model
{
    protected $fillable = [
        'course_id',
        'semester',
        'no_induk',
        'nama',
        'na',
        'nh',
        'grade_point',
    ];

    protected function casts(): array
    {
        return [
            'na' => 'decimal:2',
            'grade_point' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
