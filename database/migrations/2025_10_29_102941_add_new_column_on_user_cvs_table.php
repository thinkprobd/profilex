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
        Schema::table('user_cvs', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('preview_template_status')->default(0);
            $table->string('preview_template_image')->nullable();
            $table->string('preview_template_name')->nullable();
            $table->integer('preview_template_serial_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_cvs', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'preview_template_status',
                'preview_template_image',
                'preview_template_name',
                'preview_template_serial_number',
            ]);
        });
    }
};
