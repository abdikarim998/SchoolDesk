<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'schedule_slot_id', 'student_user_id', 'marked_by_user_id', 'lesson_date', 'status', 'note',
    ];

    protected function casts(): array
    {
        return ['lesson_date' => 'date'];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
