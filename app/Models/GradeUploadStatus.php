<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeUploadStatus extends Model
{
    protected $fillable = ['course_id', 'period', 'note'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
