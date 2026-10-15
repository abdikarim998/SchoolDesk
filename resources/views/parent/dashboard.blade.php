@extends('layouts.app')

@section('title', 'Parent dashboard')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">PARENT PORTAL · {{ $today->format('l, F j') }}</p><h1>Welcome, {{ auth()->user()->name }}</h1><p class="muted">See the timetable, attendance, and marks for your linked children.</p></div>
</section>
@forelse($childSnapshots as $snapshot)
    @php($student = $snapshot['student'])
    @php($profile = $snapshot['profile'])
    <section class="panel child-panel" id="{{ $loop->first ? 'parent-children' : 'parent-child-'.$student->id }}">
        <div class="panel-heading"><div><p class="eyebrow">LINKED STUDENT</p><h2>{{ $student->name }}</h2><p class="muted">{{ $profile?->schoolClass?->name ?? 'Class not assigned' }} · {{ $profile?->admission_number ?? 'Admission number not set' }}</p></div><span class="class-chip">Student view</span></div>
        <div class="content-grid two-columns parent-columns">
            <div><h3>Today’s lessons</h3>@forelse($snapshot['slots'] as $slot)<div class="lesson-row"><span class="time-block">{{ substr($slot->start_time, 0, 5) }}<small>{{ substr($slot->end_time, 0, 5) }}</small></span><div class="row-main"><strong>{{ $slot->assignment->course->name }}</strong><span>{{ $slot->assignment->teacher->name }}</span></div><span class="badge badge-session">{{ ucfirst($slot->session) }}</span></div>@empty<div class="empty-state compact-empty">No lessons today.</div>@endforelse</div>
            <div id="{{ $loop->first ? 'parent-attendance' : 'parent-attendance-'.$student->id }}"><h3>Recent attendance</h3>@forelse($snapshot['attendance'] as $record)<div class="list-row"><div class="row-main"><strong>{{ $record->slot->assignment->course->name }}</strong><span>{{ $record->lesson_date->format('M j, Y') }}</span></div><span class="badge badge-{{ $record->status }}">{{ ucfirst($record->status) }}</span></div>@empty<div class="empty-state compact-empty">No attendance records yet.</div>@endforelse</div>
        </div>
        <div class="content-grid two-columns parent-columns parent-lower">
            <div><h3>Courses</h3>@forelse($snapshot['assignments'] as $assignment)<div class="list-row"><div class="row-main"><strong>{{ $assignment->course->name }}</strong><span>{{ $assignment->teacher->name }}</span></div></div>@empty<div class="empty-state compact-empty">No courses assigned.</div>@endforelse</div>
            <div id="{{ $loop->first ? 'parent-marks' : 'parent-marks-'.$student->id }}"><h3>Recent marks</h3>@forelse($snapshot['marks'] as $mark)<div class="list-row"><div class="row-main"><strong>{{ $mark->assessment->title }}</strong><span>{{ $mark->assessment->assignment->course->name }}</span></div><span class="score-pill">{{ $mark->score }} <small>/ {{ $mark->assessment->max_marks }}</small></span></div>@empty<div class="empty-state compact-empty">No marks recorded yet.</div>@endforelse</div>
        </div>
        <div class="parent-weekly"><h3>Class assignments & exams</h3><p class="muted">Work posted for this class and the student’s grade when it is entered.</p>
            @forelse($snapshot['classAssessments'] as $assessment)
                @php($myMark = $assessment->marks->first())
                <div class="list-row assessment-row"><div class="row-main"><strong><span class="badge badge-session">{{ $assessment->type === 'assignment' ? 'Assignment' : 'Regular exam' }}</span> {{ $assessment->title }}</strong><span>{{ $assessment->assignment->course->name }} · {{ $assessment->assignment->teacher->name }} · {{ $assessment->assessment_date->format('M j, Y') }}@if($assessment->due_date) · Due {{ $assessment->due_date->format('M j, Y') }}@endif</span>@if($assessment->instructions)<small class="assessment-instructions">{{ $assessment->instructions }}</small>@endif</div>@if($myMark)<span class="score-pill">{{ $myMark->score }} <small>/ {{ $assessment->max_marks }}</small></span>@else<span class="badge badge-session">Not graded</span>@endif</div>
            @empty<div class="empty-state compact-empty">No class work or exams posted yet.</div>@endforelse
        </div>
        @if($snapshot['weeklySlots']->isNotEmpty())<div class="parent-weekly"><h3>Regular timetable</h3><div class="table-scroll"><table><thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Room</th></tr></thead><tbody>@foreach($snapshot['weeklySlots'] as $slot)<tr><td>{{ ucfirst($slot->day_of_week) }}</td><td>{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td><td>{{ $slot->assignment->course->name }}</td><td>{{ $slot->room ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>@endif
        @if($snapshot['upcomingSlots']->isNotEmpty())<div class="parent-weekly"><h3>Upcoming special sessions</h3><div class="compact-list">@foreach($snapshot['upcomingSlots'] as $slot)<div class="compact-row"><span><strong>{{ $slot->lesson_date->format('D, M j') }} · {{ $slot->assignment->course->name }}</strong><small>{{ ucfirst($slot->session) }} · {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</small></span></div>@endforeach</div></div>@endif
    </section>
@empty
    <section class="panel empty-state">No students are linked to your account yet. Please contact the school administrator.</section>
@endforelse
@endsection
