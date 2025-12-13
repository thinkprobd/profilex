<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class HomePageText extends Model
{
    public $table = "user_home_page_texts";
    protected $fillable = [
        "about_image",
        "about_keyword",
        "about_title",
        "technical_image",
        "technical_keyword",
        "technical_title",
        "technical_content",
        "service_keyword",
        "service_title",
        "experience_keyword",
        "experience_title",
        "achievement_image",
        "achievement_keyword",
        "achievement_title",
        "portfolio_keyword",
        "portfolio_title",
        "testimonial_keyword",
        "testimonial_title",
        "blog_keyword",
        "blog_title",
        "get_in_touch_keyword",
        "get_in_touch_title",
        "language_id",
        "user_id",
        "hero_background_image",
        "work_process_title",
        "call_to_action_title",
        "call_to_action_bg_image",
        "call_to_action_button_name",
        "call_to_action_button_url",
        "call_to_action_image",
        "hero_button_name",
        "hero_button_url",
        "about_button_name",
        "about_button_url",
        "features_title",
        "features_subtitle",
        "features_image",
        "features_image_title",
        "features_button_name",
        "features_button_url",
        "appointment_title",
        "appointment_subtitle",
        "about_video_url",
        "about_video_text",
        "hero_rating_text",
        "hero_experience_text",
        "hero_section_title",
        "hero_section_subtitle",
        "hero_section_vtitle",
        "hero_section_vsubtitle",
        "hero_section_vurl",
        "hero_video_image",
        "about_left_image",
        "about_right_image",
        "about_middle_image",
        "hero_title",
    ];
    public function language()
    {
        return $this->belongsTo('App\Models\User\Language', 'language_id');
    }
}
