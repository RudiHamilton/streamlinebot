<?php

namespace App\Services;

use App\Models\DiscordUser;
use App\Models\DiscordUserAccessTokens;
use Symfony\Component\Process\Process;

class UserAuthService
{
    //queries db and checks if user has a bot token if not method will create token and store it then search again and return it.
    public function tokenCheck($username,$discordId)
    {
        //checks if user has id in database 
        $bot_access_token = DiscordUserAccessTokens::where('discord_id',$discordId)->value('bot_access_token');
        //if they dont then no app token so run if statement
        if(empty($bot_access_token)){
            //create token and get output
            $process = $this->createToken($discordId);
            $process->run();
            $bot_access_token_unchanged = $process->getOutput();
            //sanitise the command output.
            $bot_access_token = $this->extractBotToken($bot_access_token_unchanged);
            //creates user with username,id and access token. 
            $this->createUser($username,$discordId,$bot_access_token);
        }

        return $bot_access_token;
    }
    
    //Sanitisation
    public function extractBotToken(string $rawOutput)
    {
        //bot token is currently - - - [ (weird ascii symbol)(random incrementing number)|(token) ]
        // sanitise it by removing everything before the | 
        if (strpos($rawOutput, '|') !== false) {
            $parts = explode('|', $rawOutput);
            return trim($parts[1]); // take the second half after '|'
        }
        // will fail loudly
        throw new \Exception('Invalid token format.');
    }

    // for these execs each comma seperating is a whitespace on cli

    // exec in cli for creating token 
    public function createToken(int $user_id)
    {
        return new Process([
            'php',
            'laracord',
            'bot:token',
            $user_id,
        ]);
    }
    //exec in cli for regenerating user token. will only be used every year. 
    public function regenerateToken($user_id)
    {
        return new Process([
            'php',
            'laracord',
            'bot:token',
            $user_id,
            '--regenerate',
        ]);
    }
    //simple user creation with defaults as not needed currently.
    public function createUser($username,$discordId,$access_token, $spotify_auth_token = null, $spotify_app_token = null, $spotify_app_refresh_token = null, $spotify_expires_at = null){
        DiscordUserAccessTokens::create([
            'username' => $username,
            'discord_id' => $discordId,
            'bot_access_token' => $access_token,
            'spotify_auth_token' => $spotify_auth_token,
            'spotify_app_token' => $spotify_app_token,
            'spotify_refresh_token' => $spotify_app_refresh_token,
            'spotify_expires_at' => $spotify_expires_at,
        ]);
    }
}