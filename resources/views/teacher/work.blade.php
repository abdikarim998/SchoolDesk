@extends('layouts.app')

@section('title', 'Attendance and marks')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">TEACHER WORKSPACE</p><h1>Attendance & marks</h1><p class="muted">Choose a date, open a lesson to record attendance, and enter course marks. Each period is 45 minutes; the break is 10:00–10:45 AM.</p></div>
    <a class="button button-secondary" href="{{ route('dashboard') }}">← Back to overview</a>
</section>
<section class="panel date-toolbar"><form method="GET" action="{{ route('teacher.work') }}" class="date-form"><label>Lesson date<input type="date" name="date" value="{{ $date->toDateString() }}"></label><button class="button button-primary">Show lessons</button><span class="muted">{{ $date->format('l, F j, Y') }}</span></form></section>
<section id="attendance" class="section-block">
    <div class="section-title"><div><p class="eyebrow">SESSION BY SESSION</p><h2>Lesson attendance</h2></div></div>
    @forelse($slots as $slot)
        @php($existingAttendance = $slot->attendance->keyBy('student_user_id'))
        <article class="panel attendance-panel">
            <div class="panel-heading"><div><h3>{{ $slot->assignment->course->name }} · {{ $slot->assignment->schoolClass->name }}</h3><p class="muted">{{ ucfirst($slot->session) }} · {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }} · {{ $slot->room ?? 'Room not set' }}</p></div><span class="badge badge-session">{{ $date->format('M j') }}</span></div>
            <details class="lesson-attendance"><summary>Open attendance · {{ $slot->assignment->schoolClass->students->count() }} students</summary>
            <form method="POST" action="{{ route('teacher.attendance.store') }}">
                @csrf<input type="hidden" name="slot_id" value="{{ $slot->id }}"><input type="hidden" name="date" value="{{ $date->toDateString() }}">
                <div class="table-scroll"><table><thead><tr><th>Student</th><th>Admission number</th><th>Attendance</th></tr></thead><tbody>
                    @forelse($slot->assignment->schoolClass->students as $profile)
                        @php($attendanceRecord = $existingAttendance->get($profile->user_id))
                        <tr><td><strong>{{ $profile->user->name }}</strong></td><td>{{ $profile->admission_number }}</td><td><select name="attendance[{{ $profile->user_id }}]" class="status-select"><option value="present" @selected(old('attendance.'.$profile->user_id, $attendanceRecord?->status ?? 'present') === 'present')>Present</option><option value="absent" @selected(old('attendance.'.$profile->user_id, $attendanceRecord?->status) === 'absent')>Absent</option><option value="late" @selected(old('attendance.'.$profile->user_id, $attendanceRecord?->status) === 'late')>Late</option><option value="excused" @selected(old('attendance.'.$profile->user_id, $attendanceRecord?->status) === 'excused')>Excused</option></select></td></tr>
                    @empty<tr><td colspan="3" class="empty-state">There are no students in this class yet.</td></tr>@endforelse
                </tbody></table></div>
                @if($slot->assignment->schoolClass->students->isNotEmpty())<div class="form-actions"><button class="button button-primary">Save lesson attendance</button></div>@endif
            </form></details>
        </article>
    @empty<article class="panel empty-state">There are no lessons assigned to you on {{ $date->format('l, F j') }}.</article>@endforelse
</section>

<section id="marks" class="section-block">
    <div class="section-title"><div><p class="eyebrow">ASSESSMENT BOOK</p><h2>Enter marks</h2></div></div>
    <article class="panel">
        <h3>Create class work or an exam</h3>
        <form method="POST" action="{{ route('teacher.assessments.store') }}" class="form-grid assessment-form">@csrf
            <label>Class and course<select name="course_assignment_id" required><option value="">Choose assigned course</option>@foreach($assignments as $assignment)<option value="{{ $assignment->id }}">{{ $assignment->schoolClass->name }} · {{ $assignment->course->name }}</option>@endforeach</select></label>
            <label>Type<select name="type" required><option value="assignment" @selected(old('type') === 'assignment')>Assignment</option><option value="exam" @selected(old('type', 'exam') === 'exam')>Regular exam</option></select></label>
            <label>Name<input name="title" value="{{ old('title') }}" placeholder="Homework 1 or Midterm exam" required maxlength="120"></label>
            <label>Maximum marks<input type="number" name="max_marks" min="0.01" step="0.01" required></label>
            <label>Assessment date<input type="date" name="assessment_date" value="{{ today()->toDateString() }}" required></label>
            <label>Due date <span class="optional">(optional; useful for assignments)</span><input type="date" name="due_date" value="{{ old('due_date') }}"></label>
            <label class="full-width">Instructions <span class="optional">(optional; students and parents can see these)</span><textarea name="instructions" rows="3" maxlength="5000" placeholder="Topics, pages, or other instructions">{{ old('instructions') }}</textarea></label>
            <div class="form-actions"><button class="button button-primary">Create assessment</button></div>
        </form>
    </article>
    @forelse($assessments as $assessment)
        @php($savedMarks = $assessment->marks->keyBy('student_user_id'))
        <article class="panel spaced-panel">
            <div class="panel-heading"><div><span class="badge badge-session">{{ $assessment->type === 'assignment' ? 'Assignment' : 'Regular exam' }}</span><h3 class="assessment-title">{{ $assessment->title }}</h3><p class="muted">{{ $assessment->assignment->course->name }} · {{ $assessment->assignment->schoolClass->name }} · {{ $assessment->assessment_date->format('M j, Y') }}@if($assessment->due_date) · Due {{ $assessment->due_date->format('M j, Y') }}@endif</p>@if($assessment->instructions)<p class="muted assessment-instructions">{{ $assessment->instructions }}</p>@endif</div><span class="score-pill">{{ $assessment->max_marks }} <small>maximum</small></span></div>
            <details class="assessment-edit"><summary>Edit details</summary><form method="POST" action="{{ route('teacher.assessments.update', $assessment) }}" class="form-grid assessment-edit-form">@csrf @method('PUT')
                <label>Type<select name="type" required><option value="assignment" @selected($assessment->type === 'assignment')>Assignment</option><option value="exam" @selected($assessment->type === 'exam')>Regular exam</option></select></label>
                <label>Name<input name="title" value="{{ $assessment->title }}" maxlength="120" required></label>
                <label>Maximum marks<input type="number" name="max_marks" min="0.01" max="99999" step="0.01" value="{{ $assessment->max_marks }}" required></label>
                <label>Assessment date<input type="date" name="assessment_date" value="{{ $assessment->assessment_date->toDateString() }}" required></label>
                <label>Due date<input type="date" name="due_date" value="{{ $assessment->due_date?->toDateString() }}"></label>
                <label class="full-width">Instructions<textarea name="instructions" rows="3" maxlength="5000">{{ $assessment->instructions }}</textarea></label>
                <div class="form-actions"><button class="button button-small button-primary">Save changes</button></div>
            </form></details>
            <form method="POST" action="{{ route('teacher.marks.store') }}">@csrf<input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                <div class="table-scroll"><table><thead><tr><th>Student</th><th>Admission number</th><th>Mark / {{ $assessment->max_marks }}</th></tr></thead><tbody>
                    @forelse($assessment->assignment->schoolClass->students as $profile)
                        <tr><td><strong>{{ $profile->user->name }}</strong></td><td>{{ $profile->admission_number }}</td><td><input class="score-input" type="number" name="scores[{{ $profile->user_id }}]" min="0" max="{{ $assessment->max_marks }}" step="0.01" value="{{ old('scores.'.$profile->user_id, $savedMarks->get($profile->user_id)?->score) }}" placeholder="Not entered"></td></tr>
                    @empty<tr><td colspan="3" class="empty-state">There are no students in this class yet.</td></tr>@endforelse
                </tbody></table></div>
                @if($assessment->assignment->schoolClass->students->isNotEmpty())<div class="form-actions"><button class="button button-primary">Save marks</button></div>@endif
            </form>
            <form method="POST" action="{{ route('teacher.assessments.destroy', $assessment) }}" class="assessment-delete" onsubmit="return confirm('Delete this assessment and all marks entered for it?')">@csrf @method('DELETE')<button class="button button-quiet button-small">Delete assessment</button></form>
        </article>
    @empty<div class="panel empty-state spaced-panel">No assessments yet. Create one above to start entering marks.</div>@endforelse
    @if($assessments->hasPages())<nav class="pagination-wrap" aria-label="Assessment pages">
        @if($assessments->onFirstPage())<span class="page-link disabled">Previous</span>@else<a class="page-link" href="{{ $assessments->previousPageUrl() }}#marks">Previous</a>@endif
        <span class="page-count">Page {{ $assessments->currentPage() }} of {{ $assessments->lastPage() }}</span>
        @if($assessments->hasMorePages())<a class="page-link" href="{{ $assessments->nextPageUrl() }}#marks">Next</a>@else<span class="page-link disabled">Next</span>@endif
    </nav>@endif
</section>
@endsection
