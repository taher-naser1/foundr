<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class ZoomOAuth extends Model
{
    use HasApiTokens,HasFactory;

    protected $table = 'zoom_o_auth_credentials';

    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
    ];
}
