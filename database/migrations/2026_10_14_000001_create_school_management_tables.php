<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('grade_level')->nullable();
            $table->string('section')->nullable();
            $table->string('academic_year')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('admission_number')->unique();
            $table->timestamps();
        });

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_id', 'course_id', 'teacher_id']);
        });

        Schema::create('school_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_assignment_id')->constrained()->cascadeOnDelete();
            $table->enum('schedule_type', ['weekly', 'date']);
            $table->string('day_of_week', 12)->nullable();
            $table->date('lesson_date')->nullable();
            $table->enum('session', ['morning', 'afternoon'])->default('morning');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();
            $table->index(['schedule_type', 'day_of_week']);
            $table->index(['schedule_type', 'lesson_date']);
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained('school_schedule_slots')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('marked_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('lesson_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['schedule_slot_id', 'student_user_id', 'lesson_date']);
            $table->index(['student_user_id', 'lesson_date']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('max_marks', 7, 2);
            $table->date('assessment_date');
            $table->timestamps();
        });

        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('entered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 7, 2);
            $table->string('feedback')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_user_id']);
        });

        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['parent_user_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('marks');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('school_schedule_slots');
        Schema::dropIfExists('course_assignments');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('school_classes');
    }
};
