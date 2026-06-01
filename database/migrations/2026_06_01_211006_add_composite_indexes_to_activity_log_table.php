<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->index(['log_name', 'created_at'], 'idx_log_name_created_at');
                $table->index(['subject_type', 'subject_id', 'event'], 'idx_subject_type_id_event');
                $table->index(['causer_type', 'causer_id', 'created_at'], 'idx_causer_type_id_created_at');
                $table->index(['log_name', 'event', 'created_at'], 'idx_log_name_event_created_at');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->dropIndex('idx_log_name_created_at');
                $table->dropIndex('idx_subject_type_id_event');
                $table->dropIndex('idx_causer_type_id_created_at');
                $table->dropIndex('idx_log_name_event_created_at');
            });
    }
};
