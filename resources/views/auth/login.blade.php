@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<section class="login-card">
    <a class="brand login-brand" href="/"><span class="brand-mark">S</span><span>School<span class="brand-light">Desk</span></span></a>
    <p class="eyebrow">SCHOOL MANAGEMENT PORTAL</p>
    <h1>Welcome back</h1>
    <p class="muted">Sign in to view your school dashboard.</p>
    @if($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.store') }}" class="form-stack">
        @csrf
        <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <label class="check-row"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
        <button class="button button-primary button-wide" type="submit">Sign in</button>
    </form>
    <p class="login-footnote">Your account is provided by your school administrator.</p>
</section>
@endsection
