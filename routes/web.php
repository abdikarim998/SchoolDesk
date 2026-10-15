<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/manage', [AdminController::class, 'manage'])->name('manage');
        Route::get('/manage/{section}', [AdminController::class, 'manage'])
            ->where('section', 'accounts|classes|teaching-assignments|assignments-exams|timetable|parent-links')
            ->name('manage.section');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::post('/classes', [AdminController::class, 'storeClass'])->name('classes.store');
        Route::put('/classes/{schoolClass}', [AdminController::class, 'updateClass'])->name('classes.update');
        Route::delete('/classes/{schoolClass}', [AdminController::class, 'destroyClass'])->name('classes.destroy');
        Route::post('/courses', [AdminController::class, 'storeCourse'])->name('courses.store');
        Route::put('/courses/{course}', [AdminController::class, 'updateCourse'])->name('courses.update');
        Route::delete('/courses/{course}', [AdminController::class, 'destroyCourse'])->name('courses.destroy');
        Route::post('/assignments', [AdminController::class, 'storeAssignment'])->name('assignments.store');
        Route::put('/assignments/{assignment}', [AdminController::class, 'updateAssignment'])->name('assignments.update');
        Route::delete('/assignments/{assignment}', [AdminController::class, 'destroyAssignment'])->name('assignments.destroy');
        Route::post('/assessments', [AdminController::class, 'storeAssessment'])->name('assessments.store');
        Route::put('/assessments/{assessment}', [AdminController::class, 'updateAssessment'])->name('assessments.update');
        Route::post('/assessment-marks', [AdminController::class, 'storeAssessmentMarks'])->name('assessment-marks.store');
        Route::delete('/assessments/{assessment}', [AdminController::class, 'destroyAssessment'])->name('assessments.destroy');
        Route::post('/schedule', [AdminController::class, 'storeSchedule'])->name('schedule.store');
        Route::put('/schedule/{slot}', [AdminController::class, 'updateSchedule'])->name('schedule.update');
        Route::delete('/schedule/{slot}', [AdminController::class, 'destroySchedule'])->name('schedule.destroy');
        Route::post('/parent-links', [AdminController::class, 'linkParent'])->name('parent-links.store');
        Route::delete('/parent-links/{parent}/{student}', [AdminController::class, 'unlinkParent'])->name('parent-links.destroy');
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/work', [TeacherController::class, 'work'])->name('work');
        Route::post('/attendance', [TeacherController::class, 'storeAttendance'])->name('attendance.store');
        Route::post('/assessments', [TeacherController::class, 'storeAssessment'])->name('assessments.store');
        Route::put('/assessments/{assessment}', [TeacherController::class, 'updateAssessment'])->name('assessments.update');
        Route::delete('/assessments/{assessment}', [TeacherController::class, 'destroyAssessment'])->name('assessments.destroy');
        Route::post('/marks', [TeacherController::class, 'storeMarks'])->name('marks.store');
    });
});
