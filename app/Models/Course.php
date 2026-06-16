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

    /**
     * Modul PAI tempat course ini jadi komponen. Setiap course hanya jadi
     * komponen 1 modul (lihat docs/spec.md bagian 2/4a) walau bisa muncul di
     * 2 baris module_course (curriculum lama & baru) untuk modul yang sama.
     */
    public function paiModule(): ?PaiModule
    {
        return $this->moduleCourses()->with('paiModule')->first()?->paiModule;
    }
}
