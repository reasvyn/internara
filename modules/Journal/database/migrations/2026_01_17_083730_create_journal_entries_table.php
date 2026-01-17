<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('registration_id')->index();
            $table->uuid('student_id')->index();
            $table->date('date');
            $table->string('work_topic')->nullable(); // Topik Pekerjaan
            $table->text('activity_description');
            $table->string('basic_competence')->nullable(); // Kompetensi Dasar
            $table->string('character_values')->nullable(); // Nilai-nilai Karakter
            $table->text('reflection')->nullable();
            $table->string('mood')->default('neutral');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
