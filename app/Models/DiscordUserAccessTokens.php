<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordUserAccessTokens extends Model
{
    protected $fillable = [
        'username',
        'discord_id',
        'bot_access_token',
        'spotify_app_token',
        'spotfiy_app_refresh_token',
    ];
}
