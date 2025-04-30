<?php

use App\Models\DiscordUser;
use App\Models\DiscordUserAccessTokens;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

Class CreateSpotifyToken
{
    public function checkSpotifyAppToken($discordId)
    {
        $spotifyAppToken = DiscordUserAccessTokens::where('discord_id',$discordId)->get(['spotify_app_token','spotify_expires_at']);
        if(empty($spotifyAppToken->spotify_app_token)){
            return false;
        }
        elseif($this->isTokenExpired($spotifyAppToken->spotify_app_refresh_token) == true){
            $spotifyAppToken->spotify_app_token = $this->refreshSpotifyAppToken($spotifyAppToken);
        }elseif($this->isTokenExpired($spotifyAppToken->spotify_app_refresh_token) == false){
            return $spotifyAppToken->spotify_app_token;
        }
    }

    public function isTokenExpired($spotifyAppToken)
    {
        if(Carbon::now() >= $spotifyAppToken->spotify_expires_at){
            return true;
        }else{
            return false;
        }
            
    }
    public function refreshSpotifyAppToken($spotifyAppRefreshToken)
    {
        $refreshedAccessToken = Http::post('https://accounts.spotify.com/api/token',[
            'grant_type' => 'refresh_token', 
            'refresh_token' => $spotifyAppRefreshToken,
        ]);
        $spotifyAppToken = json_decode($refreshedAccessToken->json(),true);

        $userRecord = DiscordUserAccessTokens::where('spotify_app_token',$spotifyAppToken)->get();
        $userRecord->spotify_app_token = $spotifyAppToken['refresh_token'];
        $userRecord->save();

        return $spotifyAppToken;
    }
    public function storeSpotifyAuthToken($state,$data)
    {
        $userRecord = DiscordUserAccessTokens::where('bot_access_token', $state)->get();
        $userRecord->spotify_auth_token = $data['token'];
        $userRecord->save();
    }
    public function storeSpotifyAccessTokens($bot_access_token,$spotify_auth_token,$spotify_app_token,$spotify_app_refresh_token,$spotify_expires_at): void
    {   
        DiscordUserAccessTokens::where('bot_access_token',$bot_access_token)->update([
            'spotify_auth_token' => $spotify_auth_token,
            'spotify_app_token' => $spotify_app_token,
            'spotify_app_refresh_token' => $spotify_app_refresh_token,
            'spotify_expires_at' => $spotify_expires_at,
        ]);
        // $userRecord->spotify_auth_token = $spotify_auth_token;
        // $userRecord->
        // $userRecord->spotify_app_refresh_token = $spotify_app_refresh_token;
        // $userRecord->spotify_expires_at = $spotify_expires_at;
        // $userRecord->save();
        // $userRecord = DiscordUserAccessTokens::where('bot_access_token',$bot_access_token)->first();
        return;
    }
    public function storeNewAccessToken($discordId,$refreshedAccessToken)
    {
        $userRecord = DiscordUserAccessTokens::where('discord_id',$discordId)->get();
        $userRecord->spotify_app_token = $refreshedAccessToken;
        $userRecord->spotify_expires_at = Carbon::now()->addHour();
    }
}