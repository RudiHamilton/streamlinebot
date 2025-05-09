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

        return $data['access_token'];
    }
}