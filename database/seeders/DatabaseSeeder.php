<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Mark;
use App\Models\ScheduleSlot;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo accounts and student records cannot be seeded in production.');
        }

        $this->renameLegacyClass('Form Four', 'Form Four A', 'Form Four');
        $this->renameLegacyClass('Form Three', 'Form Three A', 'Form Three');
        $this->renameLegacyClass('From one', 'Form One A', 'Form One');
        $this->renameLegacyClass('From Two', 'Form Two A', 'Form Two');

        $admin = User::updateOrCreate(['email' => 'admin@school.test'], [
            'name' => 'School Administrator', 'role' => 'admin', 'phone' => '+254 700 000 001', 'password' => 'School123!',
        ]);
        $teacher = User::updateOrCreate(['email' => 'teacher@school.test'], [
            'name' => 'Amina Hassan', 'role' => 'teacher', 'phone' => '+254 700 000 002', 'password' => 'School123!',
        ]);
        $studentOne = User::updateOrCreate(['email' => 'student@school.test'], [
            'name' => 'Hodan Ali', 'role' => 'student', 'phone' => null, 'password' => 'School123!',
        ]);
        $studentTwo = User::updateOrCreate(['email' => 'student2@school.test'], [
            'name' => 'Yusuf Mohamed', 'role' => 'student', 'phone' => null, 'password' => 'School123!',
        ]);
        $parent = User::updateOrCreate(['email' => 'parent@school.test'], [
            'name' => 'Khadra Ali', 'role' => 'parent', 'phone' => '+254 700 000 003', 'password' => 'School123!',
        ]);

        $formFour = SchoolClass::firstOrCreate(['name' => 'Form Four A'], [
            'grade_level' => 'Form Four', 'section' => 'A', 'academic_year' => Carbon::now()->year,
        ]);
        SchoolClass::firstOrCreate(['name' => 'Form Three A'], [
            'grade_level' => 'Form Three', 'section' => 'A', 'academic_year' => Carbon::now()->year,
        ]);
        StudentProfile::updateOrCreate(['user_id' => $studentOne->id], [
            'class_id' => $formFour->id, 'admission_number' => 'STU-2026-001',
        ]);
        StudentProfile::updateOrCreate(['user_id' => $studentTwo->id], [
            'class_id' => $formFour->id, 'admission_number' => 'STU-2026-002',
        ]);

        $mathematics = Course::firstOrCreate(['code' => 'MATH-401'], ['name' => 'Mathematics', 'description' => 'Core mathematics']);
        $mathAssignment = CourseAssignment::firstOrCreate([
            'class_id' => $formFour->id, 'course_id' => $mathematics->id, 'teacher_id' => $teacher->id,
        ]);

        $today = Carbon::today();
        $hasFormFourThursdaySession = ScheduleSlot::where('schedule_type', 'date')
            ->whereHas('assignment', fn ($query) => $query
                ->where('class_id', $formFour->id)
                ->where('course_id', $mathematics->id))
            ->exists();

        if (! $hasFormFourThursdaySession) {
            $thursday = $today->copy()->next(Carbon::THURSDAY);
            ScheduleSlot::create([
                'course_assignment_id' => $mathAssignment->id,
                'schedule_type' => 'date', 'lesson_date' => $thursday->toDateString(),
                'session' => 'afternoon', 'start_time' => '13:30:00', 'end_time' => '14:30:00',
                'room' => 'Room 4', 'is_cancelled' => false,
            ]);
        }

        $parent->children()->syncWithoutDetaching([$studentOne->id]);
        $assessment = Assessment::firstOrCreate([
            'course_assignment_id' => $mathAssignment->id, 'title' => 'Algebra quiz', 'assessment_date' => $today->toDateString(),
        ], ['created_by_user_id' => $teacher->id, 'max_marks' => 20]);
        Mark::updateOrCreate([
            'assessment_id' => $assessment->id, 'student_user_id' => $studentOne->id,
        ], ['entered_by_user_id' => $teacher->id, 'score' => 18]);
        Mark::updateOrCreate([
            'assessment_id' => $assessment->id, 'student_user_id' => $studentTwo->id,
        ], ['entered_by_user_id' => $teacher->id, 'score' => 16]);
        $this->call(ClassRosterSeeder::class);
        $this->call(SchoolTimetableSeeder::class);
    }

    private function renameLegacyClass(string $oldName, string $newName, string $gradeLevel): void
    {
        $legacyClass = SchoolClass::where('name', $oldName)->first();

        if (! $legacyClass || SchoolClass::where('name', $newName)->exists()) {
            return;
        }

        $legacyClass->update([
            'name' => $newName,
            'grade_level' => $gradeLevel,
            'section' => 'A',
            'academic_year' => Carbon::now()->year,
        ]);
    }
}
