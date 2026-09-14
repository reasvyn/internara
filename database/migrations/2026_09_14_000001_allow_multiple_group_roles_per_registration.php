<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_group_members', function (Blueprint $table): void {
            $table->dropUnique('group_member_registration_unique');
            $table->unique(
                ['internship_group_id', 'registration_id', 'role'],
                'group_member_registration_role_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('internship_group_members', function (Blueprint $table): void {
            $table->dropUnique('group_member_registration_role_unique');
            $table->unique(
                ['internship_group_id', 'registration_id'],
                'group_member_registration_unique',
            );
        });
    }
};
