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
        Schema::table('user_vcards', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0);
            $table->string('preview_template_image')->nullable();
            $table->tinyInteger('preview_template_status')->default(0);
            $table->integer('preview_template_serial_number')->default(0);
            $table->string('preview_template_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_vcards', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('preview_template_image');
            $table->dropColumn('preview_template_status');
            $table->dropColumn('preview_template_serial_number');
            $table->dropColumn('preview_template_name');
        });
    }
};
