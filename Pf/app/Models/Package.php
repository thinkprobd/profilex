<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $table = "packages";

    protected $fillable = [
        'title',
        'slug',
        'price',
        'term',
        'featured',
        'is_trial',
        'trial_days',
        'status',
        'features',
        'meta_keywords',
        'meta_description',
        'number_of_vcards',
        'number_of_blogs',
        'number_of_blog_categories',
        'number_of_services',
        'number_of_skills',
        'number_of_portfolios',
        'number_of_portfolio_categories',
        'number_of_languages',
        'number_of_job_expriences',
        'number_of_education',
        'themes'
    ];

    public function memberships()
    {
        return $this->hasMany('App\Models\Membership');
    }
}
