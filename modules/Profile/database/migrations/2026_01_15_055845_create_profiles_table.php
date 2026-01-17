<?php

declare(strict_types=1);

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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id')->unique()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('bio')->nullable();

            // Role-specific fields
            $table->string('nip')->nullable()->unique()->comment('For Teachers');
            $table->string('nisn')->nullable()->unique()->comment('For Students');

            $table->uuid('department_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
