<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(
            config('activitylog.table_name'),
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableUuidMorphs('subject', 'subject');
                $table->string('event')->nullable();
                $table->nullableUuidMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->json('attribute_changes')->nullable();
                $table->timestamps();

                $table->index('log_name');
                $table->index(['log_name', 'created_at'], 'idx_log_name_created_at');
                $table->index(['subject_type', 'subject_id', 'event'], 'idx_subject_type_id_event');
                $table->index(
                    ['causer_type', 'causer_id', 'created_at'],
                    'idx_causer_type_id_created_at',
                );
                $table->index(['log_name', 'event', 'created_at'], 'idx_log_name_event_created_at');
            },
        );
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(
            config('activitylog.table_name'),
        );
    }
}
