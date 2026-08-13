<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignUuid('revoked_by')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable()->after('revoked_by');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn('revoked_at');
        });
    }
};
