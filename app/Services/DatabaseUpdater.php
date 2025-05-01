<?php

namespace App\Services;

use App\Models\DiscordUserAccessTokens;
use Laracord\Services\Service;

class DatabaseUpdater extends Service
{
    /**
     * The service interval.
     */
    protected int $interval = 120;

    /**
     * Handle the service.
     */
    public function handle():mixed
    {
        $botTokens = DiscordUserAccessTokens::where('spotify_app_refresh_token',null)->get(['bot_access_token','username']);

        foreach($botTokens as $botToken){
            $botAccessToken = $botToken->bot_access_token;
            $username = $botToken->username;
            if ($botAccessToken) {
                // process a specific token
                $this->processToken($botAccessToken,$username);
            } else {
                // search for any cached tokens that matches pattern
                $this->processCachedTokens();
            }
        }
        return $this->console()->log('Spotify tokens processing complete.');
    }
    protected function processToken(string $botAccessToken,$username): void
    {
        $cacheKey = 'spotify_tokens_' . $botAccessToken;
        $tokenData = cache()->get(key: 'spotify_tokens_'.$botAccessToken);
        
        if (!$tokenData) {
            $this->console()->log("No cached token found for: {$username}");
            return;
        }
        
        $this->updateDatabase($botAccessToken, $tokenData);
        $this->console()->log("------------------------------------  Processed token for: {$username}  ------------------------------------");
        
        // Clear from cache after processing
        cache()->forget($cacheKey);
    }

    protected function updateDatabase(string $botToken, array $tokenData): void
    {
        try {
            DiscordUserAccessTokens::where('bot_access_token', $botToken)
                ->update([
                    'spotify_auth_token' => $tokenData['auth_token'],
                    'spotify_app_token' => $tokenData['access_token'],
                    'spotify_app_refresh_token' => $tokenData['refresh_token'],
                    'spotify_expires_at' => $tokenData['expires_in'],
                ]);
        } catch (\Exception $e) {
            $this->console()->log("Error updating database: {$e->getMessage()}");
        }

    }
    protected function processCachedTokens(): void
    {
        // This is a simplified approach - in a real application you might 
        // want to use a more sophisticated method to find cached keys
        $processed = 0;
        
        // This is just an example - you'd need to implement a way to list cache keys
        // that match your pattern, which depends on your cache driver
        $cacheDriver = cache()->getStore();
        
        // For simplicity, we'll just check if specific tokens are in cache
        // In a real app, you'd iterate through all matching cache keys
        $knownTokens = DiscordUserAccessTokens::pluck('bot_access_token')->toArray();
        
        foreach ($knownTokens as $token) {
            $cacheKey = 'spotify_tokens_' . $token;
            $tokenData = cache()->get($cacheKey);
            
            if ($tokenData) {
                $this->updateDatabase($token, $tokenData);
                cache()->forget($cacheKey);
                $processed++;
            }
        }
        
        $this->console()->log("Processed {$processed} cached tokens.");
    }
    
}
