<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCv extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user() {
        return $this->belongsTo('App\Models\User');
    }

    public function user_cv_sections() {
        return $this->hasMany('App\Models\User\UserCvSection');
    }
}
