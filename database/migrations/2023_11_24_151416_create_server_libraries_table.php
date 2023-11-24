<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerLibrariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_libraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("server_id");
            $table->string("library_id",100)->nullable(false);
            $table->timestamps();

            $table->foreign("server_id")->references("id")->on("servers")->onUpdate("cascade")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('server_libraries');
    }
}
