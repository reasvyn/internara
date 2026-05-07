<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['category', 'is_active']);
        });

        Schema::create('official_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('template_id')
                ->nullable()
                ->constrained('document_templates')
                ->onDelete('set null')
                ->index();

            $table->uuid('documentable_id')->index();
            $table->string('documentable_type')->index();
            $table->index(['documentable_id', 'documentable_type']);

            $table->string('title')->index();
            $table->string('document_number')->nullable()->unique();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->index(['documentable_type', 'documentable_id', 'issued_at']);
        });

        Schema::create('generated_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->string('report_type')->index();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('filters')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable()->index();
            $table->timestamps();
            $table->index(['report_type', 'status', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('official_documents');
        Schema::dropIfExists('document_templates');
    }
};
