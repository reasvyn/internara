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
        Schema::create('formal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->nullable()->constrained('document_templates')->onDelete('set null');
            
            // Polymorphic relation (User, InternshipRegistration, etc.)
            $table->uuid('documentable_id');
            $table->string('documentable_type');
            $table->index(['documentable_id', 'documentable_type']);

            $table->string('title');
            $table->string('document_number')->nullable()->unique();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formal_documents');
    }
};
