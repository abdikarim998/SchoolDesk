<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\ScheduleSlot;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolTimetableSeeder extends Seeder
{
    private const GRADES = ['Form One', 'Form Two', 'Form Three', 'Form Four'];

    private const SECTIONS = ['A', 'B', 'C', 'D'];

    private const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday'];

    private const COURSES = [
        'ENG-101' => ['English', 'English language studies'],
        'MATH-401' => ['Mathematics', 'Core mathematics'],
        'SOC-101' => ['Social Studies', 'Social and civic studies'],
        'TAR-101' => ['Tarbiya', 'Islamic studies and character education'],
        'ARB-101' => ['Arabic', 'Arabic language studies'],
        'SCI-101' => ['Science', 'General science'],
        'PHYS-401' => ['Physics', 'Core physics'],
        'GEO-101' => ['Geography', 'Physical and human geography'],
        'SOM-101' => ['Somali', 'Somali language studies'],
    ];

    private const PERIODS = [
        ['07:00:00', '07:45:00', 'morning'],
        ['07:45:00', '08:30:00', 'morning'],
        ['08:30:00', '09:15:00', 'morning'],
        ['09:15:00', '10:00:00', 'morning'],
        ['10:45:00', '11:30:00', 'afternoon'],
        ['11:30:00', '12:15:00', 'afternoon'],
    ];

    // These offsets keep teachers from being assigned to two sections at once.
    // They also preserve the original seeded Form Four A physics lesson on Tuesday
    // and place the old Wednesday mathematics lessons at their new 45-minute times.
    private const SECTION_OFFSETS = ['A' => 3, 'B' => 8, 'C' => 0, 'D' => 5];

    public function run(): void
    {
        DB::transaction(function (): void {
            $courses = [];
            foreach (self::COURSES as $code => [$name, $description]) {
                $courses[$code] = Course::updateOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'description' => $description],
                );
            }

            $teachers = $this->createTeachers();
            $classes = SchoolClass::whereIn('grade_level', self::GRADES)
                ->orderBy('grade_level')->orderBy('section')->get();
            $assignments = [];

            foreach ($classes as $class) {
                foreach (self::COURSES as $code => $_courseDetails) {
                    $teacher = $teachers[$class->grade_level][$code];
                    $assignment = CourseAssignment::where('class_id', $class->id)
                        ->where('course_id', $courses[$code]->id)->orderBy('id')->first();

                    if ($assignment) {
                        if ($assignment->teacher_id !== $teacher->id) {
                            $assignment->update(['teacher_id' => $teacher->id]);
                        }
                    } else {
                        $assignment = CourseAssignment::create([
                            'class_id' => $class->id,
                            'course_id' => $courses[$code]->id,
                            'teacher_id' => $teacher->id,
                        ]);
                    }

                    $assignments[$class->id][$code] = $assignment;
                }
            }

            $this->buildWeeklyTimetable($classes, $courses, $assignments);
            $this->seedSampleAttendance($classes);
        });
    }

    /** @return array<string, array<string, User>> */
    private function createTeachers(): array
    {
        $teachers = [];
        foreach (self::GRADES as $grade) {
            foreach (self::COURSES as $code => [$courseName]) {
                if ($code === 'MATH-401') {
                    $email = match ($grade) {
                        'Form Four' => 'teacher@school.test',
                        default => 'teacher.'.strtolower(str_replace(' ', '', $grade)).'@school.test',
                    };
                    $name = match ($grade) {
                        'Form Four' => 'Amina Hassan',
                        default => $grade.' Teacher',
                    };
                } else {
                    $gradeKey = strtolower(str_replace(' ', '', $grade));
                    $courseKey = strtolower(str_replace('-', '', $code));
                    $email = 'teacher.'.$gradeKey.'.'.$courseKey.'@school.test';
                    $name = $courseName.' Teacher ('.$grade.')';
                }

                $teachers[$grade][$code] = User::updateOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'role' => 'teacher', 'password' => 'School123!'],
                );
            }
        }

        return $teachers;
    }

    private function buildWeeklyTimetable($classes, array $courses, array $assignments): void
    {
        $courseCodes = array_keys(self::COURSES);

        foreach ($classes as $class) {
            $section = $class->section ?: 'A';
            $offset = self::SECTION_OFFSETS[$section] ?? self::SECTION_OFFSETS['A'];

            foreach (self::DAYS as $dayIndex => $day) {
                foreach (self::PERIODS as $periodIndex => [$start, $end, $session]) {
                    $courseIndex = ($periodIndex * 4 + $dayIndex * 3 + $offset) % count($courseCodes);
                    $code = $courseCodes[$courseIndex];
                    $assignment = $assignments[$class->id][$code];
                    $slotData = [
                        'course_assignment_id' => $assignment->id,
                        'schedule_type' => 'weekly',
                        'day_of_week' => $day,
                        'lesson_date' => null,
                        'session' => $session,
                        'start_time' => $start,
                        'end_time' => $end,
                        'room' => 'Room '.$class->grade_level.' '.$section,
                        'is_cancelled' => false,
                    ];

                    // Reuse old seeded lessons where possible so their attendance remains linked.
                    $slot = ScheduleSlot::where('schedule_type', 'weekly')
                        ->where('day_of_week', $day)
                        ->whereHas('assignment', fn ($query) => $query
                            ->where('class_id', $class->id)
                            ->where('course_id', $courses[$code]->id))
                        ->orderBy('id')->first();

                    if ($slot) {
                        $slot->update($slotData);
                    } else {
                        $slot = ScheduleSlot::firstOrNew([
                            'course_assignment_id' => $assignment->id,
                            'schedule_type' => 'weekly',
                            'day_of_week' => $day,
                            'start_time' => $start,
                        ]);
                        $slot->fill($slotData)->save();
                    }
                }
            }
        }
    }

    private function seedSampleAttendance($classes): void
    {
        // The starter database already contains a complete demo register. Seed a
        // sample register only on a fresh database, then leave future lesson data alone.
        if (Attendance::count() > 2) {
            return;
        }

        $lessonDate = Carbon::today();
        $day = strtolower($lessonDate->format('l'));
        if (! in_array($day, self::DAYS, true)) {
            $lessonDate = $lessonDate->previous(Carbon::WEDNESDAY);
            $day = 'wednesday';
        }

        foreach ($classes as $class) {
            $slot = ScheduleSlot::where('schedule_type', 'weekly')
                ->where('day_of_week', $day)
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $class->id))
                ->whereHas('attendance', fn ($query) => $query->whereDate('lesson_date', $lessonDate->toDateString()))
                ->orderBy('start_time')->first();

            $slot ??= ScheduleSlot::where('schedule_type', 'weekly')
                ->where('day_of_week', $day)
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $class->id))
                ->orderBy('start_time')->first();

            if (! $slot) {
                continue;
            }

            $teacherId = $slot->assignment()->value('teacher_id');
            $students = StudentProfile::where('class_id', $class->id)->orderBy('admission_number')->get();
            foreach ($students as $index => $student) {
                $sequence = $index + 1;
                $status = match ($sequence % 15) {
                    0 => 'absent',
                    1 => 'late',
                    default => 'present',
                };

                Attendance::firstOrCreate([
                    'schedule_slot_id' => $slot->id,
                    'student_user_id' => $student->user_id,
                    'lesson_date' => $lessonDate->toDateString(),
                ], ['marked_by_user_id' => $teacherId, 'status' => $status]);
            }
        }
    }
}
