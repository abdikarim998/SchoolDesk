<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    protected $table = 'school_schedule_slots';

    protected $fillable = [
        'course_assignment_id', 'schedule_type', 'day_of_week', 'lesson_date',
        'session', 'start_time', 'end_time', 'room', 'is_cancelled',
    ];

    protected function casts(): array
    {
        return ['lesson_date' => 'date', 'is_cancelled' => 'boolean'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class, 'course_assignment_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'schedule_slot_id');
    }
}
