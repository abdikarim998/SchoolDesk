<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('type', 20)->default('exam')->after('title');
            $table->text('instructions')->nullable()->after('type');
            $table->date('due_date')->nullable()->after('assessment_date');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['type', 'instructions', 'due_date']);
        });
    }
};
