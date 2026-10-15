<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\CourseAssignment;
use App\Models\Mark;
use App\Models\ScheduleSlot;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\ScheduleFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $today = Carbon::today();

        return match ($user->role) {
            'admin' => view('admin.dashboard', [
                'studentCount' => User::where('role', 'student')->count(),
                'teacherCount' => User::where('role', 'teacher')->count(),
                'parentCount' => User::where('role', 'parent')->count(),
                'classCount' => SchoolClass::count(),
                'recentAttendance' => Attendance::with(['student', 'slot.assignment.course'])
                    ->latest('lesson_date')->limit(8)->get(),
                'recentMarks' => Mark::with(['student', 'assessment.assignment.course'])
                    ->latest()->limit(8)->get(),
            ]),
            'teacher' => $this->teacherDashboard($user, $today),
            'student' => $this->studentDashboard($user, $today),
            'parent' => $this->parentDashboard($user, $today),
            default => abort(403),
        };
    }

    private function teacherDashboard(User $user, Carbon $today): View
    {
        $assignments = $user->courseAssignments()->with(['schoolClass', 'course'])->get();
        $slots = ScheduleFilter::forDate(ScheduleSlot::with(['assignment.schoolClass', 'assignment.course']), $today)
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $user->id))
            ->orderBy('start_time')->get();
        $assessments = Assessment::with(['assignment.course', 'assignment.schoolClass'])
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $user->id))
            ->latest('assessment_date')->limit(6)->get();

        return view('teacher.dashboard', compact('assignments', 'slots', 'assessments', 'today'));
    }

    private function studentDashboard(User $student, Carbon $today): View
    {
        return view('student.dashboard', [...$this->studentSnapshot($student, $today), 'today' => $today]);
    }

    private function parentDashboard(User $parent, Carbon $today): View
    {
        $children = $parent->children()->with('studentProfile.schoolClass')->orderBy('name')->get();
        $childSnapshots = $children->map(fn (User $child) => $this->studentSnapshot($child, $today))->all();

        return view('parent.dashboard', compact('childSnapshots', 'today'));
    }

    private function studentSnapshot(User $student, Carbon $today): array
    {
        $profile = $student->studentProfile()->with('schoolClass')->first();
        $classId = $profile?->class_id;
        $assignments = $classId
            ? CourseAssignment::with(['course', 'teacher'])->where('class_id', $classId)->get()
            : collect();

        $slots = collect();
        $weeklySlots = collect();
        $upcomingSlots = collect();
        if ($classId) {
            $slots = ScheduleFilter::forDate(ScheduleSlot::with(['assignment.course', 'assignment.teacher']), $today)
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $classId))
                ->orderBy('start_time')->get();
            $weeklySlots = ScheduleSlot::with(['assignment.course', 'assignment.teacher'])
                ->where('schedule_type', 'weekly')->where('is_cancelled', false)
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $classId))->get()
                ->sortBy(fn (ScheduleSlot $slot) => sprintf('%02d-%s', array_search($slot->day_of_week, ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'], true), $slot->start_time))
                ->values();
            $upcomingSlots = ScheduleSlot::with(['assignment.course', 'assignment.teacher'])
                ->where('schedule_type', 'date')->where('is_cancelled', false)
                ->whereDate('lesson_date', '>=', $today->toDateString())
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $classId))
                ->orderBy('lesson_date')->orderBy('start_time')->limit(8)->get();
        }

        $attendance = Attendance::with(['slot.assignment.course'])
            ->where('student_user_id', $student->id)->latest('lesson_date')->limit(8)->get();
        $marks = Mark::with(['assessment.assignment.course'])
            ->where('student_user_id', $student->id)->latest()->limit(8)->get();
        $classAssessments = $classId
            ? Assessment::with(['assignment.course', 'assignment.teacher', 'marks' => fn ($query) => $query->where('student_user_id', $student->id)])
                ->whereHas('assignment', fn ($query) => $query->where('class_id', $classId))
                ->orderByRaw('COALESCE(due_date, assessment_date) DESC')->get()
            : collect();

        return compact('student', 'profile', 'assignments', 'slots', 'weeklySlots', 'upcomingSlots', 'attendance', 'marks', 'classAssessments');
    }
}
