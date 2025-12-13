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
        Schema::table('seos', function (Blueprint $table) {
            $table->text('vcard_meta_keywords')->nullable();
            $table->text('vcard_meta_description')->nullable();
            $table->text('templates_meta_keywords')->nullable();
            $table->text('templates_meta_description')->nullable();
            $table->text('cv_meta_keywords')->nullable();
            $table->text('cv_meta_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seos', function (Blueprint $table) {
            $table->dropColumn([
                'vcard_meta_keywords',
                'vcard_meta_description',
                'templates_meta_keywords',
                'templates_meta_description',
                'cv_meta_keywords',
                'cv_meta_description',
            ]);
        });
    }
};
