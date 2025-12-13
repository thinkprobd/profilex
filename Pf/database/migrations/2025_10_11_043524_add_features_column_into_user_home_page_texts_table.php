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
        Schema::table('user_home_page_texts', function (Blueprint $table) {
            $table->string('features_title')->nullable();
            $table->string('features_subtitle')->nullable();
            $table->string('features_image')->nullable();
            $table->string('features_image_title')->nullable();
            $table->string('features_button_name')->nullable();
            $table->string('features_button_url')->nullable();
            $table->string('appointment_title')->nullable();
            $table->string('appointment_subtitle')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_home_page_texts', function (Blueprint $table) {
            $table->dropColumn('features_title');
            $table->dropColumn('features_subtitle');
            $table->dropColumn('features_image');
            $table->dropColumn('features_image_title');
            $table->dropColumn('features_button_name');
            $table->dropColumn('features_button_url');
        });
    }
};
