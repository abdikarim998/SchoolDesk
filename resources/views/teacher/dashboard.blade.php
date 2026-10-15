@extends('layouts.app')

@section('title', 'Teacher overview')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">TEACHER PORTAL · {{ $today->format('l, F j') }}</p><h1>Hello, {{ auth()->user()->name }}</h1><p class="muted">Your assigned lessons, attendance, and marks are in one place.</p></div>
    <a class="button button-primary" href="{{ route('teacher.work') }}">Take attendance or enter marks <span aria-hidden="true">→</span></a>
</section>
<section class="stat-grid three-cards">
    <article class="stat-card"><span class="stat-icon icon-blue">CR</span><span class="stat-label">Assigned courses</span><strong>{{ $assignments->count() }}</strong><span class="stat-note">Classes you teach</span></article>
    <article class="stat-card"><span class="stat-icon icon-violet">TD</span><span class="stat-label">Today’s lessons</span><strong>{{ $slots->count() }}</strong><span class="stat-note">Including any special sessions</span></article>
    <article class="stat-card"><span class="stat-icon icon-green">MK</span><span class="stat-label">Recent assessments</span><strong>{{ $assessments->count() }}</strong><span class="stat-note">Marks you have recorded</span></article>
</section>
<section class="content-grid two-columns">
    <article class="panel">
        <div class="panel-heading"><div><p class="eyebrow">YOUR TIMETABLE</p><h2>Today’s lessons</h2></div><a class="text-link" href="{{ route('teacher.work') }}">Open work page</a></div>
        @forelse($slots as $slot)
            <div class="lesson-row"><span class="time-block">{{ substr($slot->start_time, 0, 5) }}<small>{{ substr($slot->end_time, 0, 5) }}</small></span><div class="row-main"><strong>{{ $slot->assignment->course->name }}</strong><span>{{ $slot->assignment->schoolClass->name }} · {{ $slot->room ?? 'Room not set' }}</span></div><span class="badge badge-session">{{ ucfirst($slot->session) }}</span></div>
        @empty<div class="empty-state">No lessons are scheduled for today.</div>@endforelse
    </article>
    <article class="panel">
        <div class="panel-heading"><div><p class="eyebrow">YOUR CLASSES</p><h2>Assigned courses</h2></div></div>
        @forelse($assignments as $assignment)
            <div class="list-row"><div class="row-main"><strong>{{ $assignment->course->name }}</strong><span>{{ $assignment->schoolClass->name }} · {{ $assignment->course->code }}</span></div><span class="mini-avatar">{{ strtoupper(substr($assignment->schoolClass->name, 0, 1)) }}</span></div>
        @empty<div class="empty-state">Your administrator has not assigned any courses yet.</div>@endforelse
    </article>
</section>
<section class="panel spaced-panel"><div class="panel-heading"><div><p class="eyebrow">ASSESSMENTS</p><h2>Recent marks entered</h2></div><a class="button button-secondary button-small" href="{{ route('teacher.work') }}#marks">Enter marks</a></div>
    @forelse($assessments as $assessment)
        <div class="list-row"><div class="row-main"><strong><span class="badge badge-session">{{ $assessment->type === 'assignment' ? 'Assignment' : 'Regular exam' }}</span> {{ $assessment->title }}</strong><span>{{ $assessment->assignment->course->name }} · {{ $assessment->assignment->schoolClass->name }}</span></div><span class="score-pill">{{ $assessment->assessment_date->format('M j') }}<small> / {{ $assessment->max_marks }} max</small></span></div>
    @empty<div class="empty-state">You have not created an assessment yet.</div>@endforelse
</section>
@endsection
