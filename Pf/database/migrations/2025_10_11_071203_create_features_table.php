<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_features', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('language_id');
            $table->bigInteger('user_id');
            $table->string('image')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->integer('serial_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_features');
    }
};
