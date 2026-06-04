<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setups', function (Blueprint $table) {
            $table->unsignedBigInteger('token_version')->default(0)->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('setups', function (Blueprint $table) {
            $table->dropColumn('token_version');
        });
    }
};
