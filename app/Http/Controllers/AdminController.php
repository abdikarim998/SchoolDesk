<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Assessment;
use App\Models\Mark;
use App\Models\ScheduleSlot;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function manage(?string $section = 'accounts'): View
    {
        $sectionTitles = [
            'accounts' => 'Accounts',
            'classes' => 'Classes & courses',
            'teaching-assignments' => 'Teaching assignments',
            'assignments-exams' => 'Assignments & Exams',
            'timetable' => 'Timetable',
            'parent-links' => 'Parent links',
        ];
        abort_unless(isset($sectionTitles[$section]), 404);

        $accountSearch = trim(substr((string) request()->query('account_search', ''), 0, 100));
        $usersQuery = User::with('studentProfile.schoolClass')->orderBy('role')->orderBy('name');
        if ($accountSearch !== '') {
            $term = '%'.$accountSearch.'%';
            $usersQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)->orWhere('role', 'like', $term)
                    ->orWhereHas('studentProfile', fn ($profileQuery) => $profileQuery->where('admission_number', 'like', $term)
                        ->orWhereHas('schoolClass', fn ($classQuery) => $classQuery->where('name', 'like', $term)));
            });
        }

        $classSearch = trim(substr((string) request()->query('class_search', ''), 0, 100));
        $classRecordsQuery = SchoolClass::withCount('students')->orderBy('name');
        if ($classSearch !== '') {
            $term = '%'.$classSearch.'%';
            $classRecordsQuery->where(fn ($query) => $query->where('name', 'like', $term)
                ->orWhere('gender', 'like', $term)->orWhere('academic_year', 'like', $term));
        }

        $courseSearch = trim(substr((string) request()->query('course_search', ''), 0, 100));
        $courseRecordsQuery = Course::orderBy('name');
        if ($courseSearch !== '') {
            $term = '%'.$courseSearch.'%';
            $courseRecordsQuery->where(fn ($query) => $query->where('name', 'like', $term)
                ->orWhere('code', 'like', $term)->orWhere('description', 'like', $term));
        }

        $assignmentSearch = trim(substr((string) request()->query('assignment_search', ''), 0, 100));
        $assignmentsQuery = CourseAssignment::with(['schoolClass', 'course', 'teacher']);
        if ($assignmentSearch !== '') {
            $assignmentsQuery->where(function ($query) use ($assignmentSearch) {
                $term = '%'.$assignmentSearch.'%';
                $query->whereHas('schoolClass', fn ($classQuery) => $classQuery->where('name', 'like', $term))
                    ->orWhereHas('course', fn ($courseQuery) => $courseQuery->where('name', 'like', $term)->orWhere('code', 'like', $term))
                    ->orWhereHas('teacher', fn ($teacherQuery) => $teacherQuery->where('name', 'like', $term));
            });
        }
        $assignments = $assignmentsQuery->orderByDesc('id')->paginate(25, ['*'], 'assignments_page')->withQueryString();

        $assessmentSearch = trim(substr((string) request()->query('assessment_search', ''), 0, 100));
        $assessmentsQuery = Assessment::with([
            'assignment.schoolClass.students.user', 'assignment.course', 'assignment.teacher', 'marks',
        ])->latest('assessment_date');
        if ($assessmentSearch !== '') {
            $term = '%'.$assessmentSearch.'%';
            $assessmentsQuery->where(function ($query) use ($term) {
                $query->where('title', 'like', $term)->orWhere('type', 'like', $term)
                    ->orWhere('instructions', 'like', $term)
                    ->orWhereHas('assignment.schoolClass', fn ($classQuery) => $classQuery->where('name', 'like', $term))
                    ->orWhereHas('assignment.course', fn ($courseQuery) => $courseQuery->where('name', 'like', $term)->orWhere('code', 'like', $term))
                    ->orWhereHas('assignment.teacher', fn ($teacherQuery) => $teacherQuery->where('name', 'like', $term));
            });
        }

        $scheduleSearch = trim(substr((string) request()->query('schedule_search', ''), 0, 100));
        $schedulesQuery = ScheduleSlot::with(['assignment.schoolClass', 'assignment.course', 'assignment.teacher'])
            ->orderByRaw("CASE WHEN schedule_type = 'weekly' THEN 0 ELSE 1 END")
            ->orderBy('day_of_week')->orderBy('lesson_date')->orderBy('start_time');
        if ($scheduleSearch !== '') {
            $term = '%'.$scheduleSearch.'%';
            $schedulesQuery->where(function ($query) use ($term) {
                $query->where('room', 'like', $term)->orWhere('day_of_week', 'like', $term)
                    ->orWhere('session', 'like', $term)->orWhere('lesson_date', 'like', $term)
                    ->orWhereHas('assignment.schoolClass', fn ($classQuery) => $classQuery->where('name', 'like', $term))
                    ->orWhereHas('assignment.course', fn ($courseQuery) => $courseQuery->where('name', 'like', $term)->orWhere('code', 'like', $term))
                    ->orWhereHas('assignment.teacher', fn ($teacherQuery) => $teacherQuery->where('name', 'like', $term));
            });
        }

        $parentLinkSearch = trim(substr((string) request()->query('parent_link_search', ''), 0, 100));
        $linkedParentsQuery = User::where('role', 'parent')->whereHas('children')
            ->with('children.studentProfile.schoolClass')->orderBy('name');
        if ($parentLinkSearch !== '') {
            $term = '%'.$parentLinkSearch.'%';
            $linkedParentsQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term)
                    ->orWhereHas('children', fn ($childQuery) => $childQuery->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('studentProfile', fn ($profileQuery) => $profileQuery->where('admission_number', 'like', $term)
                            ->orWhereHas('schoolClass', fn ($classQuery) => $classQuery->where('name', 'like', $term))));
            });
        }
        $parentOptions = User::where('role', 'parent')->orderBy('name')->get();

        return view('admin.manage', [
            'activeSection' => $section,
            'sectionTitle' => $sectionTitles[$section],
            'accountSearch' => $accountSearch,
            'classSearch' => $classSearch,
            'courseSearch' => $courseSearch,
            'assignmentSearch' => $assignmentSearch,
            'assessmentSearch' => $assessmentSearch,
            'scheduleSearch' => $scheduleSearch,
            'parentLinkSearch' => $parentLinkSearch,
            'users' => $usersQuery->paginate(25, ['*'], 'users_page')->withQueryString(),
            'classes' => SchoolClass::withCount('students')->orderBy('name')->get(),
            'courses' => Course::orderBy('name')->get(),
            'classRecords' => $classRecordsQuery->paginate(20, ['*'], 'classes_page')->withQueryString(),
            'courseRecords' => $courseRecordsQuery->paginate(20, ['*'], 'courses_page')->withQueryString(),
            'assignments' => $assignments,
            'courseAssignments' => CourseAssignment::with(['schoolClass', 'course', 'teacher'])->orderBy('class_id')->orderBy('course_id')->get(),
            'assessments' => $assessmentsQuery->paginate(10, ['*'], 'assessments_page')->withQueryString(),
            'schedules' => $schedulesQuery->paginate(25, ['*'], 'schedule_page')->withQueryString(),
            'parents' => $parentOptions,
            'linkedParents' => $linkedParentsQuery->get(),
            'students' => User::where('role', 'student')->with('studentProfile.schoolClass')->orderBy('name')->get(),
            'teachers' => User::where('role', 'teacher')->orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student', 'parent'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8'],
            'class_id' => ['required_if:role,student', 'nullable', 'exists:school_classes,id'],
            'admission_number' => ['required_if:role,student', 'nullable', 'string', 'max:40', 'unique:student_profiles,admission_number'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            if ($user->role === 'student') {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'class_id' => $data['class_id'],
                    'admission_number' => $data['admission_number'],
                ]);
            }
        });

        return back()->with('status', 'User account created.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8'],
            'class_id' => [$user->role === 'student' ? 'required' : 'nullable', 'nullable', 'exists:school_classes,id'],
            'admission_number' => [$user->role === 'student' ? 'required' : 'nullable', 'nullable', 'string', 'max:40', Rule::unique('student_profiles', 'admission_number')->ignore($user->id, 'user_id')],
        ]);
        $user->update(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null]);
        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }
        if ($user->role === 'student') {
            $user->studentProfile()->updateOrCreate(['user_id' => $user->id], [
                'class_id' => $data['class_id'], 'admission_number' => $data['admission_number'],
            ]);
        }

        return back()->with('status', 'Account details updated.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:school_classes,name'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'class_members' => ['required', 'integer', 'min:0', 'max:500'],
            'academic_year' => ['required', 'string', 'max:30'],
        ]);
        SchoolClass::create($data);

        return back()->with('status', 'Class created.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school_classes', 'name')->ignore($schoolClass->id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'class_members' => ['required', 'integer', 'min:0', 'max:500'],
            'academic_year' => ['required', 'string', 'max:30'],
        ]);
        $schoolClass->update($data);

        return back()->with('status', 'Class details updated.');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:courses,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        Course::create($data);

        return back()->with('status', 'Course created.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('courses', 'code')->ignore($course->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $course->update($data);

        return back()->with('status', 'Course details updated.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:school_classes,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['required', 'exists:users,id'],
        ]);
        abort_unless(User::whereKey($data['teacher_id'])->where('role', 'teacher')->exists(), 422, 'Choose a teacher account.');
        CourseAssignment::firstOrCreate($data);

        return back()->with('status', 'Course assigned to the class and teacher.');
    }

    public function updateAssignment(Request $request, CourseAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'exists:school_classes,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'teacher_id' => ['required', 'exists:users,id'],
        ]);
        abort_unless(User::whereKey($data['teacher_id'])->where('role', 'teacher')->exists(), 422, 'Choose a teacher account.');
        $assignment->update($data);

        return back()->with('status', 'Teaching assignment updated.');
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

        $assignment = CourseAssignment::with('schoolClass')->findOrFail($data['course_assignment_id']);
        Assessment::create([
            ...$data,
            'created_by_user_id' => auth()->id(),
            'instructions' => $data['instructions'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);

        return back()->with('status', ucfirst($data['type']).' created for '.$assignment->schoolClass->name.'.');
    }

    public function updateAssessment(Request $request, Assessment $assessment): RedirectResponse
    {
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

    public function storeAssessmentMarks(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'assessment_id' => ['required', 'exists:assessments,id'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assessment = Assessment::with('assignment')->findOrFail($data['assessment_id']);
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

        return back()->with('status', 'Student grades saved.');
    }

    public function destroyAssessment(Assessment $assessment): RedirectResponse
    {
        $assessment->delete();

        return back()->with('status', 'Assessment and its student grades deleted.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $data = $this->validatedSchedule($request);
        ScheduleSlot::create($data);

        return back()->with('status', 'Timetable session created.');
    }

    public function updateSchedule(Request $request, ScheduleSlot $slot): RedirectResponse
    {
        $slot->update($this->validatedSchedule($request));

        return back()->with('status', 'Timetable session updated.');
    }

    private function validatedSchedule(Request $request): array
    {
        $data = $request->validate([
            'course_assignment_id' => ['required', 'exists:course_assignments,id'],
            'schedule_type' => ['required', Rule::in(['weekly', 'date'])],
            'day_of_week' => ['required_if:schedule_type,weekly', 'nullable', Rule::in(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])],
            'lesson_date' => ['required_if:schedule_type,date', 'nullable', 'date'],
            'session' => ['required', Rule::in(['morning', 'afternoon'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:60'],
        ]);

        $data['day_of_week'] = $data['schedule_type'] === 'weekly' ? $data['day_of_week'] : null;
        $data['lesson_date'] = $data['schedule_type'] === 'date' ? $data['lesson_date'] : null;
        $data['is_cancelled'] = $request->boolean('is_cancelled');

        return $data;
    }

    public function linkParent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'parent_user_id' => ['required', 'exists:users,id'],
            'student_user_id' => ['required', 'exists:users,id'],
        ]);
        $parent = User::whereKey($data['parent_user_id'])->where('role', 'parent')->firstOrFail();
        $student = User::whereKey($data['student_user_id'])->where('role', 'student')->firstOrFail();
        $parent->children()->syncWithoutDetaching([$student->id]);

        return back()->with('status', 'Parent linked to student.');
    }

    public function unlinkParent(User $parent, User $student): RedirectResponse
    {
        abort_unless($parent->role === 'parent' && $student->role === 'student', 404);
        $parent->children()->detach($student->id);

        return back()->with('status', 'Parent link removed.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot delete the account you are using.');
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'The last administrator account cannot be deleted.']);
        }
        $user->delete();

        return back()->with('status', 'User account deleted.');
    }

    public function destroyClass(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->delete();

        return back()->with('status', 'Class deleted.');
    }

    public function destroyCourse(Course $course): RedirectResponse
    {
        $course->delete();

        return back()->with('status', 'Course deleted.');
    }

    public function destroyAssignment(CourseAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('status', 'Course assignment deleted.');
    }

    public function destroySchedule(ScheduleSlot $slot): RedirectResponse
    {
        $slot->delete();

        return back()->with('status', 'Timetable session deleted.');
    }
}
