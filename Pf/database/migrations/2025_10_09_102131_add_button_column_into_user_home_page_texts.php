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
            $table->string('hero_button_name')->nullable();
            $table->string('hero_button_url')->nullable();
            $table->string('about_button_name')->nullable();
            $table->string('about_button_url')->nullable();
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
            $table->dropColumn('hero_button_name');
            $table->dropColumn('hero_button_url');
            $table->dropColumn('about_button_name');
            $table->dropColumn('about_button_url');
        });
    }
};
