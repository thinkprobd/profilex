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
            $table->string('hero_section_title')->nullable();
            $table->string('hero_section_subtitle')->nullable();
            $table->string('hero_section_vtitle')->nullable();
            $table->string('hero_section_vsubtitle')->nullable();
            $table->string('hero_section_vurl')->nullable();
            $table->string('hero_video_image')->nullable();
            $table->string('about_left_image')->nullable();
            $table->string('about_right_image')->nullable();
            $table->string('about_middle_image')->nullable();
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
            $table->dropColumn([
                'hero_section_title',
                'hero_section_subtitle',
                'hero_section_vtitle',
                'hero_section_vsubtitle',
                'hero_section_vurl',
                'hero_video_image',
                'about_left_image',
                'about_right_image',
                'about_middle_image',
            ]);
        });
    }
};
