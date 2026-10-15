<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class ScheduleFilter
{
    public static function forDate(Builder $query, CarbonInterface $date): Builder
    {
        $day = strtolower($date->format('l'));

        return $query->where('is_cancelled', false)->where(function (Builder $slots) use ($day, $date) {
            $slots->where(function (Builder $weekly) use ($day) {
                $weekly->where('schedule_type', 'weekly')->where('day_of_week', $day);
            })->orWhere(function (Builder $specific) use ($date) {
                $specific->where('schedule_type', 'date')->whereDate('lesson_date', $date->toDateString());
            });
        });
    }
}
