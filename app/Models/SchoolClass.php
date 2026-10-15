<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'school_classes';

    protected $fillable = ['name', 'grade_level', 'section', 'gender', 'class_members', 'academic_year'];

    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'class_id');
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'class_id');
    }
}
