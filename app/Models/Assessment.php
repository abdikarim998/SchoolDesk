<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'course_assignment_id', 'created_by_user_id', 'title', 'type', 'instructions',
        'max_marks', 'assessment_date', 'due_date',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date', 'due_date' => 'date', 'max_marks' => 'decimal:2'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class, 'course_assignment_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
