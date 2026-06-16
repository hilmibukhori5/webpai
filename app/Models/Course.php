<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sks',
    ];

    public function moduleCourses(): HasMany
    {
        return $this->hasMany(ModuleCourse::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(CourseGrade::class);
    }

    public function threshold(): HasOne
    {
        return $this->hasOne(CourseThreshold::class);
    }
}
