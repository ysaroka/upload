<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServerConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('auth');
            $table->string('path');
            $table->integer('server_id')->unsigned()->nullable();
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null')->onUpdate('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['auth', 'path', 'server_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('server_configurations');
    }
}
