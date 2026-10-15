@extends('layouts.app')

@section('title', $sectionTitle)

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">SCHOOL MANAGEMENT</p><h1>{{ $sectionTitle }}</h1><p class="muted">Manage this part of the school portal.</p></div>
    <a class="button button-secondary" href="{{ route('dashboard') }}">← Back to overview</a>
</section>
@if($activeSection === 'accounts')
<section id="users" class="section-block">
    <div class="section-title"><div><p class="eyebrow">PEOPLE</p><h2>Accounts</h2></div></div>
    <div class="content-grid two-columns align-start">
        <article class="panel">
            <h3>Create an account</h3>
            <form method="POST" action="{{ route('admin.users.store') }}" class="form-grid">
                @csrf
                <label>Full name<input name="name" value="{{ old('name') }}" required maxlength="120"></label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
                <label>Role<select name="role" required><option value="">Choose role</option>@foreach(['admin'=>'Administrator','teacher'=>'Teacher','student'=>'Student','parent'=>'Parent'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label>Phone <span class="optional">optional</span><input name="phone" value="{{ old('phone') }}"></label>
                <label>Initial password<input type="password" name="password" minlength="8" required></label>
                <label>Student class <span class="optional">students only</span><select name="class_id"><option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></label>
                <label>Admission number <span class="optional">students only</span><input name="admission_number" maxlength="40"></label>
                <div class="form-actions"><button class="button button-primary" type="submit">Create account</button></div>
            </form>
        </article>
        <article class="panel table-panel">
            <div class="panel-heading"><div><h3>School accounts <span class="count-badge">{{ $users->total() }}</span></h3><p class="muted">Search names, email, role, class or admission number.</p></div></div>
            <form method="GET" action="{{ route('admin.manage') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="account_search" value="{{ $accountSearch }}" placeholder="Find a person or account" aria-label="Search accounts" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($accountSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage') }}">Clear</a>@endif
            </form>
            <div class="table-scroll"><table><thead><tr><th>Name</th><th>Role</th><th>Class / contact</th><th></th></tr></thead><tbody>
                @forelse($users as $user)
                    <tr><td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td><td><span class="role-tag role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td><td>{{ $user->studentProfile?->schoolClass?->name ?? $user->phone ?? '—' }}</td><td><details class="edit-details"><summary>Edit</summary><form method="POST" action="{{ route('admin.users.update', $user) }}" class="edit-form">@csrf @method('PUT')<label>Name<input name="name" value="{{ $user->name }}" required></label><label>Email<input type="email" name="email" value="{{ $user->email }}" required></label><label>Phone<input name="phone" value="{{ $user->phone }}"></label><label>Set new password <span class="optional">leave blank to keep current</span><input type="password" name="password" minlength="8"></label>@if($user->role === 'student')<label>Class<select name="class_id" required>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($user->studentProfile?->class_id === $class->id)>{{ $class->name }}</option>@endforeach</select></label><label>Admission number<input name="admission_number" value="{{ $user->studentProfile?->admission_number }}" required></label>@endif<button class="button button-small button-primary">Save</button></form></details><form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="icon-button danger-text" title="Delete account" aria-label="Delete account">×</button></form></td></tr>
                @empty<tr><td colspan="4" class="empty-state">{{ $accountSearch !== '' ? 'No accounts match your search.' : 'No accounts yet.' }}</td></tr>@endforelse
            </tbody></table></div>
            <p class="muted">Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} accounts</p>
            {{ $users->withQueryString()->fragment('users')->links() }}
        </article>
    </div>
</section>
@endif

@if($activeSection === 'classes')
<section id="classes" class="section-block">
    <div class="section-title"><div><p class="eyebrow">CURRICULUM</p><h2>Classes & courses</h2></div></div>
    <div class="content-grid two-columns align-start">
        <article class="panel"><div class="panel-heading"><div><h3>Create a class</h3><p class="muted">Add or update a class, then search the class list.</p></div></div>
            <form method="POST" action="{{ route('admin.classes.store') }}" class="form-grid">@csrf
                <label>Class name<input name="name" placeholder="Form Four" value="{{ old('name') }}" required></label>
                <label>Class gender<select name="gender" required><option value="">Select gender</option><option value="male" @selected(old('gender') === 'male')>Male</option><option value="female" @selected(old('gender') === 'female')>Female</option></select></label>
                <label>Class members<input type="number" name="class_members" min="0" max="500" step="1" placeholder="e.g. 35" value="{{ old('class_members') }}" required></label>
                <label>Class year<input name="academic_year" placeholder="2026" value="{{ old('academic_year') }}" required></label>
                <div class="form-actions"><button class="button button-primary">Add class</button></div>
            </form>
            <div class="list-subheading"><h4>School classes <span class="count-badge">{{ $classRecords->total() }}</span></h4></div>
            <form method="GET" action="{{ route('admin.manage.section', 'classes') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="class_search" value="{{ $classSearch }}" placeholder="Find a class, year or gender" aria-label="Search classes" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($classSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'classes') }}">Clear</a>@endif
            </form>
            <div class="compact-list record-list">@forelse($classRecords as $class)<div class="compact-row record-row"><span><strong>{{ $class->name }}</strong><small>{{ ucfirst($class->gender ?? 'Gender not set') }} · {{ $class->class_members ?? '—' }} members · {{ $class->students_count }} enrolled · {{ $class->academic_year ?? 'Year not set' }}</small></span><details class="edit-details"><summary>Edit</summary><form method="POST" action="{{ route('admin.classes.update', $class) }}" class="edit-form">@csrf @method('PUT')<label>Class name<input name="name" value="{{ $class->name }}" required></label><label>Class gender<select name="gender" required><option value="">Select gender</option><option value="male" @selected($class->gender === 'male')>Male</option><option value="female" @selected($class->gender === 'female')>Female</option></select></label><label>Class members<input type="number" name="class_members" min="0" max="500" step="1" value="{{ $class->class_members }}" required></label><label>Class year<input name="academic_year" value="{{ $class->academic_year }}" placeholder="2026" required></label><button class="button button-small button-primary">Save</button></form></details><form method="POST" action="{{ route('admin.classes.destroy', $class) }}" onsubmit="return confirm('Delete this class and its teaching assignments?')">@csrf @method('DELETE')<button class="icon-button danger-text" aria-label="Delete class">×</button></form></div>@empty<div class="empty-state">{{ $classSearch !== '' ? 'No classes match your search.' : 'No classes created yet.' }}</div>@endforelse</div>
            <p class="muted">Showing {{ $classRecords->firstItem() ?? 0 }}–{{ $classRecords->lastItem() ?? 0 }} of {{ $classRecords->total() }} classes</p>
            {{ $classRecords->withQueryString()->links() }}
        </article>
        <article class="panel"><div class="panel-heading"><div><h3>Create a course</h3><p class="muted">Add a course or search by its name and code.</p></div></div>
            <form method="POST" action="{{ route('admin.courses.store') }}" class="form-grid">@csrf
                <label>Course name<input name="name" placeholder="Mathematics" required></label>
                <label>Course code<input name="code" placeholder="MATH-401" required></label>
                <label class="full-width">Description <span class="optional">optional</span><textarea name="description" rows="2"></textarea></label>
                <div class="form-actions"><button class="button button-primary">Add course</button></div>
            </form>
            <div class="list-subheading"><h4>Courses <span class="count-badge">{{ $courseRecords->total() }}</span></h4></div>
            <form method="GET" action="{{ route('admin.manage.section', 'classes') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="course_search" value="{{ $courseSearch }}" placeholder="Find a course or code" aria-label="Search courses" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($courseSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'classes') }}">Clear</a>@endif
            </form>
            <div class="compact-list record-list">@forelse($courseRecords as $course)<div class="compact-row record-row"><span><strong>{{ $course->name }}</strong><small>{{ $course->code }}</small></span><details class="edit-details"><summary>Edit</summary><form method="POST" action="{{ route('admin.courses.update', $course) }}" class="edit-form">@csrf @method('PUT')<label>Course name<input name="name" value="{{ $course->name }}" required></label><label>Course code<input name="code" value="{{ $course->code }}" required></label><label>Description<textarea name="description" rows="2">{{ $course->description }}</textarea></label><button class="button button-small button-primary">Save</button></form></details><form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course and its assignments?')">@csrf @method('DELETE')<button class="icon-button danger-text" aria-label="Delete course">×</button></form></div>@empty<div class="empty-state">{{ $courseSearch !== '' ? 'No courses match your search.' : 'No courses created yet.' }}</div>@endforelse</div>
            <p class="muted">Showing {{ $courseRecords->firstItem() ?? 0 }}–{{ $courseRecords->lastItem() ?? 0 }} of {{ $courseRecords->total() }} courses</p>
            {{ $courseRecords->withQueryString()->links() }}
        </article>
    </div>
</section>
@endif

@if($activeSection === 'teaching-assignments')
<section id="assignments" class="section-block">
    <div class="section-title"><div><p class="eyebrow">STAFFING</p><h2>Teaching assignments</h2></div><a class="button button-secondary" href="{{ route('admin.manage.section', 'assignments-exams') }}">Assignments & Exams →</a></div>
    <div class="content-grid two-columns align-start">
        <article class="panel"><h3>Assign a teacher</h3>
            <form method="POST" action="{{ route('admin.assignments.store') }}" class="form-grid">@csrf
                <label>Class<select name="class_id" required><option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></label>
                <label>Course<select name="course_id" required><option value="">Choose course</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->name }} · {{ $course->code }}</option>@endforeach</select></label>
                <label class="full-width">Teacher<select name="teacher_id" required><option value="">Choose teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></label>
                <div class="form-actions"><button class="button button-primary">Save assignment</button></div>
            </form>
        </article>
        <article class="panel table-panel assignments-panel">
            <div class="panel-heading assignment-list-heading"><div><h3>Current assignments</h3><p class="muted">{{ $assignments->total() }} {{ $assignments->total() === 1 ? 'assignment' : 'assignments' }}{{ $assignmentSearch !== '' ? ' found' : ' total' }}</p></div><span class="count-badge">{{ $assignments->total() }}</span></div>
            <form method="GET" action="{{ route('admin.manage.section', 'teaching-assignments') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="assignment_search" value="{{ $assignmentSearch }}" placeholder="Find a class, course or teacher" aria-label="Search by class, course or teacher" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($assignmentSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'teaching-assignments') }}">Clear</a>@endif
            </form>
            <div class="compact-list assignment-list">@forelse($assignments as $assignment)<div class="compact-row assignment-row"><span><strong>{{ $assignment->course->name }} · {{ $assignment->schoolClass->name }}</strong><small>{{ $assignment->teacher->name }}</small></span><details class="edit-details"><summary>Edit</summary><form method="POST" action="{{ route('admin.assignments.update', $assignment) }}" class="edit-form">@csrf @method('PUT')<label>Class<select name="class_id">@foreach($classes as $class)<option value="{{ $class->id }}" @selected($assignment->class_id === $class->id)>{{ $class->name }}</option>@endforeach</select></label><label>Course<select name="course_id">@foreach($courses as $course)<option value="{{ $course->id }}" @selected($assignment->course_id === $course->id)>{{ $course->name }}</option>@endforeach</select></label><label>Teacher<select name="teacher_id">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($assignment->teacher_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></label><button class="button button-small button-primary">Save</button></form></details><form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}">@csrf @method('DELETE')<button class="icon-button danger-text" aria-label="Remove assignment">×</button></form></div>@empty<div class="empty-state">{{ $assignmentSearch !== '' ? 'No assignments match your search.' : 'No course assignments yet.' }}</div>@endforelse</div>
            <p class="muted">Showing {{ $assignments->firstItem() ?? 0 }}–{{ $assignments->lastItem() ?? 0 }} of {{ $assignments->total() }} assignments</p>
            {{ $assignments->withQueryString()->fragment('assignments')->links() }}
        </article>
    </div>
</section>
@endif

@if($activeSection === 'assignments-exams')
<section id="assessments" class="section-block">
    <div class="section-title assessment-section-heading"><div><p class="eyebrow">ACADEMIC RECORDS</p><h2>Assignments & Exams <span class="count-badge">{{ $assessments->total() }}</span></h2><p class="muted">Create work for a class, then enter each student’s assignment or exam grade here.</p></div><a class="button button-secondary" href="{{ route('admin.manage') }}">Add or enrol students</a></div>
    <div class="content-grid two-columns align-start">
        <article class="panel">
            <h3>Post an assignment or exam</h3>
            <p class="muted">Choose a class, course, and teacher. All students enrolled in that class will appear in its gradebook.</p>
            <form method="POST" action="{{ route('admin.assessments.store') }}" class="form-grid assessment-form">@csrf
                <label>Class · course · teacher<select name="course_assignment_id" required><option value="">Choose class and course</option>@foreach($courseAssignments as $courseAssignment)<option value="{{ $courseAssignment->id }}" @selected(old('course_assignment_id') == $courseAssignment->id)>{{ $courseAssignment->schoolClass->name }} · {{ $courseAssignment->course->name }} · {{ $courseAssignment->teacher->name }}</option>@endforeach</select></label>
                <label>Type<select name="type" required><option value="assignment" @selected(old('type') === 'assignment')>Assignment</option><option value="exam" @selected(old('type', 'exam') === 'exam')>Regular exam</option></select></label>
                <label>Name<input name="title" value="{{ old('title') }}" placeholder="Homework 1 or Midterm exam" maxlength="120" required></label>
                <label>Maximum marks<input type="number" name="max_marks" value="{{ old('max_marks') }}" min="0.01" max="99999" step="0.01" required></label>
                <label>Date<input type="date" name="assessment_date" value="{{ old('assessment_date', today()->toDateString()) }}" required></label>
                <label>Due date <span class="optional">optional</span><input type="date" name="due_date" value="{{ old('due_date') }}"></label>
                <label class="full-width">Instructions <span class="optional">shown to students and parents</span><textarea name="instructions" rows="3" maxlength="5000" placeholder="What students need to complete or prepare">{{ old('instructions') }}</textarea></label>
                <div class="form-actions"><button class="button button-primary">Post assignment / exam</button></div>
            </form>
            @if($courseAssignments->isEmpty())<div class="empty-state">Assign a teacher to a class and course above before posting class work.</div>@endif
        </article>
        <article class="panel table-panel assessment-admin-list">
            <div class="panel-heading"><div><h3>Posted assignments & exams <span class="count-badge">{{ $assessments->total() }}</span></h3><p class="muted">Search by title, class, course or teacher.</p></div></div>
            <form method="GET" action="{{ route('admin.manage.section', 'assignments-exams') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="assessment_search" value="{{ $assessmentSearch }}" placeholder="Find an assignment, exam or class" aria-label="Search assignments and exams" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($assessmentSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'assignments-exams') }}">Clear</a>@endif
            </form>
            @forelse($assessments as $assessment)
                @php($savedMarks = $assessment->marks->keyBy('student_user_id'))
                <div class="admin-assessment-card">
                    <div class="admin-assessment-heading"><div><span class="badge badge-session">{{ $assessment->type === 'assignment' ? 'Assignment' : 'Regular exam' }}</span><strong>{{ $assessment->title }}</strong><p class="muted">{{ $assessment->assignment->schoolClass->name }} · {{ $assessment->assignment->course->name }} · {{ $assessment->assignment->teacher->name }}</p><small>{{ $assessment->assessment_date->format('M j, Y') }}@if($assessment->due_date) · Due {{ $assessment->due_date->format('M j, Y') }}@endif · Max {{ $assessment->max_marks }}</small>@if($assessment->instructions)<p class="muted assessment-instructions">{{ $assessment->instructions }}</p>@endif</div></div>
                    <details class="assessment-edit"><summary>Edit assessment details</summary><form method="POST" action="{{ route('admin.assessments.update', $assessment) }}" class="form-grid assessment-edit-form">@csrf @method('PUT')
                        <label>Type<select name="type" required><option value="assignment" @selected($assessment->type === 'assignment')>Assignment</option><option value="exam" @selected($assessment->type === 'exam')>Regular exam</option></select></label>
                        <label>Name<input name="title" value="{{ $assessment->title }}" maxlength="120" required></label>
                        <label>Maximum marks<input type="number" name="max_marks" value="{{ $assessment->max_marks }}" min="0.01" max="99999" step="0.01" required></label>
                        <label>Date<input type="date" name="assessment_date" value="{{ $assessment->assessment_date->toDateString() }}" required></label>
                        <label>Due date<input type="date" name="due_date" value="{{ $assessment->due_date?->toDateString() }}"></label>
                        <label class="full-width">Instructions<textarea name="instructions" rows="3" maxlength="5000">{{ $assessment->instructions }}</textarea></label>
                        <div class="form-actions"><button class="button button-small button-primary">Save changes</button></div>
                    </form></details>
                    <details class="gradebook-details"><summary>Students and grades · {{ $assessment->assignment->schoolClass->students->count() }} students</summary>
                        <form method="POST" action="{{ route('admin.assessment-marks.store') }}">@csrf<input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                            <div class="table-scroll"><table><thead><tr><th>Student</th><th>Admission no.</th><th>Grade / {{ $assessment->max_marks }}</th></tr></thead><tbody>
                                @forelse($assessment->assignment->schoolClass->students as $profile)
                                    <tr><td><strong>{{ $profile->user->name }}</strong></td><td>{{ $profile->admission_number }}</td><td><input class="score-input" type="number" name="scores[{{ $profile->user_id }}]" value="{{ old('scores.'.$profile->user_id, $savedMarks->get($profile->user_id)?->score) }}" min="0" max="{{ $assessment->max_marks }}" step="0.01" placeholder="Not entered"></td></tr>
                                @empty<tr><td colspan="3" class="empty-state">No students are enrolled in this class. Add them under Accounts.</td></tr>@endforelse
                            </tbody></table></div>
                            @if($assessment->assignment->schoolClass->students->isNotEmpty())<div class="form-actions"><button class="button button-primary">Save student grades</button></div>@endif
                        </form>
                    </details>
                    <form method="POST" action="{{ route('admin.assessments.destroy', $assessment) }}" class="assessment-delete" onsubmit="return confirm('Delete this assessment and all student grades entered for it?')">@csrf @method('DELETE')<button class="button button-quiet button-small">Delete</button></form>
                </div>
            @empty<div class="empty-state">{{ $assessmentSearch !== '' ? 'No assignments or exams match your search.' : 'No assignments or exams posted yet.' }}</div>@endforelse
            <p class="muted">Showing {{ $assessments->firstItem() ?? 0 }}–{{ $assessments->lastItem() ?? 0 }} of {{ $assessments->total() }} posted assignments and exams</p>
            {{ $assessments->withQueryString()->fragment('assessments')->links() }}
        </article>
    </div>
</section>
@endif

@if($activeSection === 'timetable')
<section id="schedule" class="section-block">
    <div class="section-title"><div><p class="eyebrow">WEEKLY & SPECIAL SESSIONS</p><h2>Timetable</h2><p class="muted">Regular lessons run Saturday–Wednesday: four 45-minute periods from 7:00 AM, a 45-minute break from 10:00–10:45 AM, then two periods. Add a date-specific Form Four session for an occasional Thursday morning or afternoon.</p></div></div>
    <article class="panel">
        <h3>Add a lesson</h3>
        <form method="POST" action="{{ route('admin.schedule.store') }}" class="form-grid schedule-form">@csrf
            <label>Class · course · teacher<select name="course_assignment_id" required><option value="">Choose teaching assignment</option>@foreach($courseAssignments as $assignment)<option value="{{ $assignment->id }}">{{ $assignment->schoolClass->name }} · {{ $assignment->course->name }} · {{ $assignment->teacher->name }}</option>@endforeach</select></label>
            <label>Schedule type<select name="schedule_type" required><option value="weekly">Weekly recurring</option><option value="date">One specific date</option></select></label>
            <label>Day <span class="optional">weekly only</span><select name="day_of_week"><option value="">Choose day</option>@foreach(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'] as $day)<option value="{{ $day }}">{{ ucfirst($day) }}</option>@endforeach</select></label>
            <label>Date <span class="optional">specific session only</span><input type="date" name="lesson_date"></label>
            <label>Session<select name="session" required><option value="morning">Morning</option><option value="afternoon">Afternoon</option></select></label>
            <label>Room <span class="optional">optional</span><input name="room" placeholder="Room 4"></label>
            <label>Starts<input type="time" name="start_time" required></label>
            <label>Ends<input type="time" name="end_time" required></label>
            <div class="form-actions"><button class="button button-primary">Add to timetable</button></div>
        </form>
    </article>
    <article class="panel table-panel spaced-panel"><div class="panel-heading"><div><h3>Scheduled lessons <span class="count-badge">{{ $schedules->total() }}</span></h3><p class="muted">Search by class, course, teacher, day, room or date.</p></div></div>
        <form method="GET" action="{{ route('admin.manage.section', 'timetable') }}" class="assignment-search data-search-form">
            <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="schedule_search" value="{{ $scheduleSearch }}" placeholder="Find a class, teacher, day or room" aria-label="Search timetable" maxlength="100"></label>
            <button class="button button-small button-primary" type="submit">Search</button>
            @if($scheduleSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'timetable') }}">Clear</a>@endif
        </form>
        <div class="table-scroll"><table class="schedule-table"><thead><tr><th>Class & course</th><th>When</th><th>Session</th><th>Time & room</th><th>Update / remove</th></tr></thead><tbody>
        @forelse($schedules as $slot)
            <tr><td><strong>{{ $slot->assignment->schoolClass->name }}</strong><small>{{ $slot->assignment->course->name }} · {{ $slot->assignment->teacher->name }}</small></td>
                <td>{{ $slot->schedule_type === 'weekly' ? ucfirst($slot->day_of_week).' · Weekly' : $slot->lesson_date->format('M j, Y') }}</td><td><span class="badge {{ $slot->is_cancelled ? 'badge-absent' : 'badge-session' }}">{{ $slot->is_cancelled ? 'Cancelled' : ucfirst($slot->session) }}</span></td><td>{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}<small>{{ $slot->room ?? 'Room not set' }}</small></td>
                <td><form method="POST" action="{{ route('admin.schedule.update', $slot) }}" class="inline-edit">@csrf @method('PUT')
                    <input type="hidden" name="course_assignment_id" value="{{ $slot->course_assignment_id }}"><input type="hidden" name="schedule_type" value="{{ $slot->schedule_type }}">
                    @if($slot->schedule_type === 'weekly')<select name="day_of_week">@foreach(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'] as $day)<option value="{{ $day }}" @selected($slot->day_of_week === $day)>{{ ucfirst($day) }}</option>@endforeach</select>@else<input type="date" name="lesson_date" value="{{ $slot->lesson_date->format('Y-m-d') }}">@endif
                    <select name="session"><option value="morning" @selected($slot->session === 'morning')>Morning</option><option value="afternoon" @selected($slot->session === 'afternoon')>Afternoon</option></select>
                    <input type="time" name="start_time" value="{{ substr($slot->start_time, 0, 5) }}" required><input type="time" name="end_time" value="{{ substr($slot->end_time, 0, 5) }}" required><input name="room" value="{{ $slot->room }}" placeholder="Room">
                    <label class="cancelled-check"><input type="checkbox" name="is_cancelled" value="1" @checked($slot->is_cancelled)> Cancel session</label>
                    <button class="button button-small button-secondary">Save</button>
                </form><form class="delete-inline" method="POST" action="{{ route('admin.schedule.destroy', $slot) }}" onsubmit="return confirm('Remove this timetable session?')">@csrf @method('DELETE')<button class="text-link danger-text">Remove</button></form></td></tr>
        @empty<tr><td colspan="5" class="empty-state">{{ $scheduleSearch !== '' ? 'No timetable entries match your search.' : 'No timetable entries yet.' }}</td></tr>@endforelse
        </tbody></table></div>
        <p class="muted">Showing {{ $schedules->firstItem() ?? 0 }}–{{ $schedules->lastItem() ?? 0 }} of {{ $schedules->total() }} scheduled lessons</p>
        {{ $schedules->withQueryString()->fragment('schedule')->links() }}
    </article>
</section>
@endif

@if($activeSection === 'parent-links')
<section id="parents" class="section-block">
    <div class="section-title"><div><p class="eyebrow">FAMILY ACCESS</p><h2>Parent links</h2><p class="muted">A parent can be linked to more than one student.</p></div></div>
    <div class="content-grid two-columns align-start">
        <article class="panel">
            <div class="panel-heading"><div><h3>Link a parent to a student</h3><p class="muted">Search either list, select both people, then save the link.</p></div></div>
            <form method="POST" action="{{ route('admin.parent-links.store') }}" class="form-grid parent-link-form">@csrf
                <div class="searchable-choice" data-searchable-choice>
                    <label>Find a parent<input type="search" data-choice-search placeholder="Type a parent name or email" autocomplete="off"></label>
                    <label>Parent<select name="parent_user_id" data-choice-select required><option value="">Choose parent</option>@foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }} · {{ $parent->email }}</option>@endforeach</select></label>
                    <p class="muted choice-empty" data-choice-empty hidden>No matching parents.</p>
                </div>
                <div class="searchable-choice" data-searchable-choice>
                    <label>Find a student<input type="search" data-choice-search placeholder="Type a student, class or admission no." autocomplete="off"></label>
                    <label>Student<select name="student_user_id" data-choice-select required><option value="">Choose student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->studentProfile?->schoolClass?->name ?? 'No class' }} · {{ $student->studentProfile?->admission_number ?? 'No admission no.' }}</option>@endforeach</select></label>
                    <p class="muted choice-empty" data-choice-empty hidden>No matching students.</p>
                </div>
                <div class="form-actions"><button class="button button-primary">Link parent and student</button></div>
            </form>
        </article>
        <article class="panel table-panel parent-links-panel">
            <div class="panel-heading"><div><h3>Current parent links <span class="count-badge">{{ $linkedParents->sum(fn ($parent) => $parent->children->count()) }}</span></h3><p class="muted">Search parents, students, classes or admission numbers.</p></div></div>
            <form method="GET" action="{{ route('admin.manage.section', 'parent-links') }}" class="assignment-search data-search-form">
                <label class="search-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg><input type="search" name="parent_link_search" value="{{ $parentLinkSearch }}" placeholder="Find a parent or linked student" aria-label="Search parent links" maxlength="100"></label>
                <button class="button button-small button-primary" type="submit">Search</button>
                @if($parentLinkSearch !== '')<a class="button button-small button-secondary search-clear" href="{{ route('admin.manage.section', 'parent-links') }}">Clear</a>@endif
            </form>
            <div class="compact-list record-list parent-link-list">@forelse($linkedParents as $parent)@foreach($parent->children as $child)<div class="compact-row record-row"><span><strong>{{ $parent->name }} → {{ $child->name }}</strong><small>{{ $child->studentProfile?->schoolClass?->name ?? 'Class not assigned' }} · {{ $child->studentProfile?->admission_number ?? 'No admission no.' }}</small></span><form method="POST" action="{{ route('admin.parent-links.destroy', [$parent, $child]) }}">@csrf @method('DELETE')<button class="text-link danger-text">Unlink</button></form></div>@endforeach @empty<div class="empty-state">{{ $parentLinkSearch !== '' ? 'No parent links match your search.' : 'No parent-student links yet.' }}</div>@endforelse</div>
        </article>
    </div>
</section>
@endif
@endsection
