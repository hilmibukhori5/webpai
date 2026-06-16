<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionCourse extends Model
{
    protected $fillable = [
        'submission_id',
        'course_id',
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
