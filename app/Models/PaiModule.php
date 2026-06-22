<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /** Hanya modul yang punya course terdaftar untuk prodi tertentu. */
    public function scopeForProdi(Builder $query, string $prodi): void
    {
        $query->whereHas('moduleCourses', fn (Builder $q) => $q->where('prodi', $prodi));
    }

    /**
     * Matkul komponen modul ini untuk satu kurikulum tertentu.
     */
    public function coursesForCurriculum(string $curriculum): Collection
    {
        return $this->moduleCourses()
            ->where('curriculum', $curriculum)
            ->with('course')
            ->get()
            ->pluck('course');
    }

    /** Matkul unik yang terdaftar untuk prodi tertentu (semua kurikulum digabung). */
    public function uniqueCoursesForProdi(string $prodi): Collection
    {
        return $this->moduleCourses()
            ->where('prodi', $prodi)
            ->with('course')
            ->get()
            ->pluck('course')
            ->unique('id')
            ->values();
    }
}
