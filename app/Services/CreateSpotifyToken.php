<?php

namespace App\Services;

use App\Models\DiscordUser;
use App\Models\DiscordUserAccessTokens;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

Class CreateSpotifyToken
{
    public function checkSpotifyAppToken($discordId)
    {
        $tokenData = DiscordUserAccessTokens::where('discord_id',$discordId)->first(['spotify_app_token','spotify_app_refresh_token','spotify_expires_at']);
        
        $spotifyAppToken = $tokenData['spotify_app_token'];
        $spotifyAppRefreshToken = $tokenData['spotify_app_refresh_token'];
        $spotifyExpiresAt = $tokenData['spotify_expires_at']; // stored as 2025-04-30 14:32:51 

        if($spotifyAppToken == null){
            return false;
        }

        if($this->isTokenExpired($spotifyExpiresAt) == true){

            $spotifyAppToken = $this->refreshSpotifyAppToken($spotifyAppRefreshToken);
            return $spotifyAppToken;

        }
        return $spotifyAppToken;
    }

    public function isTokenExpired($spotifyExpiresAt): bool
    {

        $expiryDate = Carbon::parse($spotifyExpiresAt);
        $timeNow = Carbon::now()->addHour();
        
        return $timeNow->greaterThanOrEqualTo($expiryDate);
            
    }
    public function refreshSpotifyAppToken($spotifyAppRefreshToken)
    {

        $clientId = config('app.spotify_client_id'); 
        $clientSecret = config('app.spotify_client_secret'); 

        $credentials = base64_encode($clientId . ':' . $clientSecret);

        $refreshedAccessToken = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . $credentials,
        ])
        ->asForm()
        ->post('https://accounts.spotify.com/api/token',[
            'grant_type' => 'refresh_token', 
            'refresh_token' => $spotifyAppRefreshToken,
        ]);

        $data = $refreshedAccessToken->json();

        $expiresAt = Carbon::now()->addHour();
        

        DiscordUserAccessTokens::where('spotify_app_refresh_token',$spotifyAppRefreshToken)
            ->update([
                'spotify_app_token' => $data['access_token'],
                'spotify_expires_at' => $expiresAt->addHour(),
            ]);

        // $userRecord->spotify_app_token = $data['access_token'];
        // $userRecord->spotify_expires_at = $expiresAt->addHour();
        // $userRecord->save();

        return $data['access_token'];
    }
    // public function storeSpotifyAuthToken($state,$data)
    // {
    //     $userRecord = DiscordUserAccessTokens::where('bot_access_token', $state)->get();
    //     $userRecord->spotify_auth_token = $data['token'];
    //     $userRecord->save();
    // }
    // public function storeSpotifyAccessTokens($bot_access_token,$spotify_auth_token,$spotify_app_token,$spotify_app_refresh_token,$spotify_expires_at): void
    // {   
    //     DiscordUserAccessTokens::where('bot_access_token',$bot_access_token)->update([
    //         'spotify_auth_token' => $spotify_auth_token,
    //         'spotify_app_token' => $spotify_app_token,
    //         'spotify_app_refresh_token' => $spotify_app_refresh_token,
    //         'spotify_expires_at' => $spotify_expires_at,
    //     ]);
    //     // $userRecord->spotify_auth_token = $spotify_auth_token;
    //     // $userRecord->
    //     // $userRecord->spotify_app_refresh_token = $spotify_app_refresh_token;
    //     // $userRecord->spotify_expires_at = $spotify_expires_at;
    //     // $userRecord->save();
    //     // $userRecord = DiscordUserAccessTokens::where('bot_access_token',$bot_access_token)->first();
    //     return;
    // }
    // public function storeNewAccessToken($discordId,$refreshedAccessToken)
    // {
    //     $userRecord = DiscordUserAccessTokens::where('discord_id',$discordId)->get();
    //     $userRecord->spotify_app_token = $refreshedAccessToken;
    //     $userRecord->spotify_expires_at = Carbon::now()->addHour();
    // }
}