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
            $table->string('hero_background_image')->nullable();
            $table->string('work_process_title')->nullable();
            $table->string('call_to_action_title')->nullable();
            $table->string('call_to_action_bg_image')->nullable();
            $table->string('call_to_action_button_name')->nullable();
            $table->string('call_to_action_button_url')->nullable();
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
            $table->dropColumn('hero_background_image');
            $table->dropColumn('work_process_title');
            $table->dropColumn('call_to_action_title');
            $table->dropColumn('call_to_action_bg_image');
            $table->dropColumn('call_to_action_button_name');
            $table->dropColumn('call_to_action_button_url');
        });
    }
};
