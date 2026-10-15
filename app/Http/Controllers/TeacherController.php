<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\CourseAssignment;
use App\Models\Mark;
use App\Models\ScheduleSlot;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\ScheduleFilter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function work(Request $request): View
    {
        $request->validate(['date' => ['nullable', 'date']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $teacherId = auth()->id();
        $slots = ScheduleFilter::forDate(ScheduleSlot::with([
            'assignment.course', 'assignment.schoolClass.students.user',
            'attendance' => fn ($query) => $query->whereDate('lesson_date', $date->toDateString()),
        ]), $date)
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacherId))
            ->orderBy('start_time')->get();
        $assignments = CourseAssignment::with(['course', 'schoolClass'])
            ->where('teacher_id', $teacherId)->orderBy('class_id')->get();
        $assessments = Assessment::with(['assignment.course', 'assignment.schoolClass.students.user', 'marks'])
            ->whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest('assessment_date')->paginate(12, ['*'], 'assessment_page');

        return view('teacher.work', compact('date', 'slots', 'assignments', 'assessments'));
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot_id' => ['required', 'exists:school_schedule_slots,id'],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
        ]);

        $slot = ScheduleSlot::with('assignment')->findOrFail($data['slot_id']);
        abort_unless($slot->assignment->teacher_id === auth()->id(), 403);
        abort_if($slot->is_cancelled, 422, 'This lesson is cancelled.');
        abort_unless(ScheduleFilter::forDate(ScheduleSlot::query()->whereKey($slot->id), Carbon::parse($data['date']))->exists(), 422, 'This lesson is not scheduled for the selected date.');

        $studentIds = StudentProfile::where('class_id', $slot->assignment->class_id)
            ->whereIn('user_id', array_keys($data['attendance']))->pluck('user_id')->all();
        abort_unless(count($studentIds) === count($data['attendance']), 422, 'Attendance includes a student outside this class.');

        DB::transaction(function () use ($data, $studentIds, $slot) {
            foreach ($studentIds as $studentId) {
                Attendance::updateOrCreate(
                    ['schedule_slot_id' => $slot->id, 'student_user_id' => $studentId, 'lesson_date' => $data['date']],
                    ['marked_by_user_id' => auth()->id(), 'status' => $data['attendance'][$studentId]],
                );
            }
        });

        return back()->with('status', 'Lesson attendance saved.');
    }

    public function storeAssessment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_assignment_id' => ['required', 'exists:course_assignments,id'],
            'type' => ['required', Rule::in(['assignment', 'exam'])],
            'title' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'max_marks' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'assessment_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);
        $assignment = CourseAssignment::whereKey($data['course_assignment_id'])
            ->where('teacher_id', auth()->id())->firstOrFail();
        $assignment->assessments()->create([
            'created_by_user_id' => auth()->id(),
            'title' => $data['title'],
            'type' => $data['type'],
            'instructions' => $data['instructions'] ?? null,
            'max_marks' => $data['max_marks'],
            'assessment_date' => $data['assessment_date'],
            'due_date' => $data['due_date'] ?? null,
        ]);

        return back()->with('status', ucfirst($data['type']).' created for '.$assignment->schoolClass()->value('name').'.');
    }

    public function updateAssessment(Request $request, Assessment $assessment): RedirectResponse
    {
        $assessment->load('assignment');
        abort_unless($assessment->assignment->teacher_id === auth()->id(), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['assignment', 'exam'])],
            'title' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'max_marks' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'assessment_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        if ($assessment->marks()->where('score', '>', $data['max_marks'])->exists()) {
            return back()->withErrors(['max_marks' => 'Maximum marks cannot be lower than a score already entered.']);
        }

        $assessment->update($data);

        return back()->with('status', 'Assessment details updated.');
    }

    public function destroyAssessment(Assessment $assessment): RedirectResponse
    {
        $assessment->load('assignment');
        abort_unless($assessment->assignment->teacher_id === auth()->id(), 403);
        $assessment->delete();

        return back()->with('status', 'Assessment and its marks deleted.');
    }

    public function storeMarks(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'assessment_id' => ['required', 'exists:assessments,id'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assessment = Assessment::with('assignment')->findOrFail($data['assessment_id']);
        abort_unless($assessment->assignment->teacher_id === auth()->id(), 403);
        foreach ($data['scores'] as $score) {
            if ($score !== null && (float) $score > (float) $assessment->max_marks) {
                return back()->withErrors(['scores' => 'A mark cannot be higher than the assessment maximum.']);
            }
        }

        $studentIds = StudentProfile::where('class_id', $assessment->assignment->class_id)
            ->whereIn('user_id', array_map('intval', array_keys($data['scores'])))->pluck('user_id')->all();
        abort_unless(count($studentIds) === count($data['scores']), 422, 'Marks include a student outside this class.');

        DB::transaction(function () use ($data, $studentIds, $assessment) {
            foreach ($studentIds as $studentId) {
                $score = $data['scores'][$studentId];
                if ($score === null || $score === '') {
                    Mark::where('assessment_id', $assessment->id)->where('student_user_id', $studentId)->delete();
                    continue;
                }
                Mark::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'student_user_id' => $studentId],
                    ['entered_by_user_id' => auth()->id(), 'score' => $score],
                );
            }
        });

        return back()->with('status', 'Student marks saved.');
    }
}
