@extends('layouts.app')

@section('title', 'Student dashboard')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">STUDENT PORTAL · {{ $today->format('l, F j') }}</p><h1>Welcome, {{ $student->name }}</h1><p class="muted">View your courses, timetable, attendance, and marks.</p></div>
    @if($profile?->schoolClass)<span class="class-chip">{{ $profile->schoolClass->name }} <small>{{ $profile->admission_number }}</small></span>@endif
</section>
<section class="stat-grid three-cards">
    <article class="stat-card"><span class="stat-icon icon-blue">CR</span><span class="stat-label">My courses</span><strong>{{ $assignments->count() }}</strong><span class="stat-note">Courses in your class</span></article>
    <article class="stat-card"><span class="stat-icon icon-violet">AT</span><span class="stat-label">Attendance entries</span><strong>{{ $attendance->count() }}</strong><span class="stat-note">Recent lesson records</span></article>
    <article class="stat-card"><span class="stat-icon icon-green">MK</span><span class="stat-label">Marks recorded</span><strong>{{ $marks->count() }}</strong><span class="stat-note">Recent assessment marks</span></article>
</section>
<section class="content-grid two-columns">
    <article class="panel" id="student-lessons"><div class="panel-heading"><div><p class="eyebrow">YOUR DAY</p><h2>Today’s lessons</h2></div></div>
        @forelse($slots as $slot)<div class="lesson-row"><span class="time-block">{{ substr($slot->start_time, 0, 5) }}<small>{{ substr($slot->end_time, 0, 5) }}</small></span><div class="row-main"><strong>{{ $slot->assignment->course->name }}</strong><span>{{ $slot->assignment->teacher->name }} · {{ $slot->room ?? 'Room not set' }}</span></div><span class="badge badge-session">{{ ucfirst($slot->session) }}</span></div>@empty<div class="empty-state">No lessons are scheduled for you today.</div>@endforelse
    </article>
    <article class="panel" id="student-courses"><div class="panel-heading"><div><p class="eyebrow">MY COURSES</p><h2>Enrolled courses</h2></div></div>
        @forelse($assignments as $assignment)<div class="list-row"><div class="row-main"><strong>{{ $assignment->course->name }}</strong><span>{{ $assignment->course->code }} · {{ $assignment->teacher->name }}</span></div><span class="mini-avatar">{{ strtoupper(substr($assignment->course->name, 0, 1)) }}</span></div>@empty<div class="empty-state">Your courses will appear here when the administrator assigns your class.</div>@endforelse
    </article>
</section>
<section class="panel spaced-panel" id="student-timetable"><div class="panel-heading"><div><p class="eyebrow">WEEKLY VIEW</p><h2>Regular timetable</h2></div></div>
    @if($weeklySlots->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Teacher</th><th>Room</th></tr></thead><tbody>@foreach($weeklySlots as $slot)<tr><td><span class="weekday-pill">{{ ucfirst($slot->day_of_week) }}</span></td><td>{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td><td><strong>{{ $slot->assignment->course->name }}</strong></td><td>{{ $slot->assignment->teacher->name }}</td><td>{{ $slot->room ?? '—' }}</td></tr>@endforeach</tbody></table></div>@else<div class="empty-state">Your regular timetable has not been added yet.</div>@endif
</section>
<section class="content-grid two-columns spaced-panel">
    <article class="panel"><div class="panel-heading"><div><p class="eyebrow">SPECIAL SESSIONS</p><h2>Upcoming date changes</h2></div></div>
        @forelse($upcomingSlots as $slot)<div class="list-row"><div class="row-main"><strong>{{ $slot->lesson_date->format('D, M j') }} · {{ $slot->assignment->course->name }}</strong><span>{{ ucfirst($slot->session) }} · {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }} · {{ $slot->assignment->teacher->name }}</span></div><span class="badge badge-session">Special</span></div>@empty<div class="empty-state">No special sessions are scheduled.</div>@endforelse
    </article>
    <article class="panel" id="student-attendance"><div class="panel-heading"><div><p class="eyebrow">MY ATTENDANCE</p><h2>Recent lessons</h2></div></div>
        @forelse($attendance as $record)<div class="list-row"><div class="row-main"><strong>{{ $record->slot->assignment->course->name }}</strong><span>{{ $record->lesson_date->format('M j, Y') }}</span></div><span class="badge badge-{{ $record->status }}">{{ ucfirst($record->status) }}</span></div>@empty<div class="empty-state">Your attendance records will appear here.</div>@endforelse
    </article>
</section>
<section class="panel spaced-panel"><div class="panel-heading"><div><p class="eyebrow">ASSESSMENTS</p><h2>My marks</h2></div></div>
    @forelse($marks as $mark)<div class="list-row"><div class="row-main"><strong>{{ $mark->assessment->title }}</strong><span>{{ $mark->assessment->assignment->course->name }} · {{ $mark->assessment->assessment_date->format('M j, Y') }}</span></div><span class="score-pill">{{ $mark->score }} <small>/ {{ $mark->assessment->max_marks }}</small></span></div>@empty<div class="empty-state">Your marks will appear here when a teacher records them.</div>@endforelse
</section>
<section class="panel spaced-panel" id="student-assessments"><div class="panel-heading"><div><p class="eyebrow">CLASS WORK</p><h2>Assignments & exams</h2><p class="muted">Work and exam details posted for your class, with your grade when it is entered.</p></div></div>
    @forelse($classAssessments as $assessment)
        @php($myMark = $assessment->marks->first())
        <div class="list-row assessment-row"><div class="row-main"><strong><span class="badge badge-session">{{ $assessment->type === 'assignment' ? 'Assignment' : 'Regular exam' }}</span> {{ $assessment->title }}</strong><span>{{ $assessment->assignment->course->name }} · {{ $assessment->assignment->teacher->name }} · {{ $assessment->assessment_date->format('M j, Y') }}@if($assessment->due_date) · Due {{ $assessment->due_date->format('M j, Y') }}@endif</span>@if($assessment->instructions)<small class="assessment-instructions">{{ $assessment->instructions }}</small>@endif</div>@if($myMark)<span class="score-pill">{{ $myMark->score }} <small>/ {{ $assessment->max_marks }}</small></span>@else<span class="badge badge-session">Not graded</span>@endif</div>
    @empty<div class="empty-state">Your teachers have not posted class work or exams yet.</div>@endforelse
</section>
@endsection
