<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('reason')->nullable();
            $table->string('model_type');
            $table->string('model_id');
            $table->index(['model_type', 'model_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('statuses');
    }
}
