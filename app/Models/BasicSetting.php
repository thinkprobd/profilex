<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasicSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        "language_id",
        'intro_subtitle',
        'intro_title',
        'intro_text',
        'intro_main_image',
        'team_section_title',
        'team_section_subtitle',
        'feature_section',
        'process_section',
        'templates_section',
        'featured_users_section',
        'pricing_section',
        'partners_section',
        'intro_section',
        'testimonial_section',
        'news_section',
        'top_footer_section',
        'copyright_section',
        'footer_text',
        'copyright_text',
        'footer_logo',
        'maintainance_mode',
        'maintainance_text',
        'maintenance_img',
        'maintenance_status',
        'secret_path',
        'user_registraion_status',
        'user_registraion_countries',
        'user_registration_deactive_img',
        'user_registration_deactive_text',
        'user_login_attempts'
    ];

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }
}
