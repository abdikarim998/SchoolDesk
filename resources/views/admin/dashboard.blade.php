@extends('layouts.app')

@section('title', 'Administrator overview')

@section('content')
<section class="page-heading">
    <div><p class="eyebrow">ADMINISTRATION</p><h1>Good day, {{ auth()->user()->name }}</h1><p class="muted">Your school at a glance. You manage accounts, classes, courses, and schedules here.</p></div>
    <a class="button button-primary" href="{{ route('admin.manage') }}">Manage school <span aria-hidden="true">→</span></a>
</section>
<section class="stat-grid">
    <article class="stat-card"><span class="stat-icon icon-blue">ST</span><span class="stat-label">Students</span><strong>{{ $studentCount }}</strong><span class="stat-note">Active student accounts</span></article>
    <article class="stat-card"><span class="stat-icon icon-violet">TC</span><span class="stat-label">Teachers</span><strong>{{ $teacherCount }}</strong><span class="stat-note">Teaching staff accounts</span></article>
    <article class="stat-card"><span class="stat-icon icon-green">PA</span><span class="stat-label">Parents</span><strong>{{ $parentCount }}</strong><span class="stat-note">Linked family accounts</span></article>
    <article class="stat-card"><span class="stat-icon icon-amber">CL</span><span class="stat-label">Classes</span><strong>{{ $classCount }}</strong><span class="stat-note">School class groups</span></article>
</section>
<section class="content-grid two-columns">
    <article class="panel">
        <div class="panel-heading"><div><p class="eyebrow">LATEST RECORDS</p><h2>Recent attendance</h2></div><a class="text-link" href="{{ route('admin.manage') }}#schedule">Manage timetable</a></div>
        @forelse($recentAttendance as $record)
            <div class="list-row"><div class="row-main"><strong>{{ $record->student->name }}</strong><span>{{ $record->slot->assignment->course->name }} · {{ $record->lesson_date->format('M j, Y') }}</span></div><span class="badge badge-{{ $record->status }}">{{ ucfirst($record->status) }}</span></div>
        @empty
            <div class="empty-state">Attendance records will appear here after a teacher takes attendance.</div>
        @endforelse
    </article>
    <article class="panel">
        <div class="panel-heading"><div><p class="eyebrow">ACADEMIC PROGRESS</p><h2>Recent marks</h2></div><a class="text-link" href="{{ route('admin.manage') }}#users">Manage users</a></div>
        @forelse($recentMarks as $record)
            <div class="list-row"><div class="row-main"><strong>{{ $record->student->name }}</strong><span>{{ $record->assessment->assignment->course->name }} · {{ $record->assessment->title }}</span></div><span class="score-pill">{{ $record->score }} <small>/ {{ $record->assessment->max_marks }}</small></span></div>
        @empty
            <div class="empty-state">Marks will appear here after teachers add assessments.</div>
        @endforelse
    </article>
</section>
<section class="callout"><div><strong>Keep the school information up to date</strong><p>Create accounts, assign teachers, and set each class timetable.</p></div><a class="button button-secondary" href="{{ route('admin.manage') }}">Open school management</a></section>
@endsection
