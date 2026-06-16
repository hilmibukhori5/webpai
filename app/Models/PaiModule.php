<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaiModule extends Model
{
    protected $fillable = [
        'code',
        'official_code',
        'name',
        'percentile',
    ];

    public function moduleCourses(): HasMany
    {
        return $this->hasMany(ModuleCourse::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Matkul komponen modul ini untuk satu kurikulum tertentu.
     */
    public function coursesForCurriculum(string $curriculum)
    {
        return $this->moduleCourses()
            ->where('curriculum', $curriculum)
            ->with('course')
            ->get()
            ->pluck('course');
    }
}
