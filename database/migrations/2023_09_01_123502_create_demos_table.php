<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->string('email',255)->nullable(false);
            $table->string('password',255)->nullable(false);
            $table->integer('hours')->nullable(false);
            $table->datetime('start_date')->nullable(false);
            $table->datetime('end_date')->nullable(false);
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demos');
    }
}
