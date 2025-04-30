<?php

namespace App\Http;

use App\Models\DiscordUserAccessTokens;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laracord\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class StreamlineApiController extends Controller
{
    //will return the users query to a json that will hold 2 values.
    public function search(Request $request)
    {
        // return response()->json([
        //     'token' => $userToken,
        //     'websiteUsed' => $websiteUsed,
        //     'userQuery' => $userQuery,
        // ]);
    }

    public function spotifyAuthCallback(Request $request)
    {

        $code = $request->query('code');
        $state = $request->query('state'); // this is the bot_access_token passed in state
        
        if (!$code) {
            return response()->json(['error' => 'No code provided'], 400);
        }

        // exchange code for access token yup
        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('discord.ngrok') . '/api/spotify-auth-callback',
            'client_id' => config('app.spotify_client_id'),
            'client_secret' => config('app.spotify_client_secret'),
        ]);

        if ($response->failed()) {
            Log::error('Spotify token exchange failed', ['response' => $response->body()]);
            return response()->json(['error' => 'Token exchange failed'], 500);
        }

        $data = $response->json();
        $tokenExpiresAt = Carbon::now()->addHour();
        var_dump($tokenExpiresAt->toDateTimeString());
        cache()->put(
            key: 'spotify_tokens_' . $state, 
            value: [
                'auth_token' => $code,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $tokenExpiresAt,
                'cached_at' => now(),
            ],
            ttl: now()->addDay() // cached for a day to ensure that the bot will run the command at the end of the day
        );
        return redirect('https://open.spotify.com/');

        //(new CreateSpotifyToken())->storeSpotifyAccessTokens(bot_access_token: $state, spotify_auth_token: $code, spotify_app_token: $data['access_token'], spotify_app_refresh_token: $data['refresh_token'],spotify_expires_at: $data['expires_in']);

       
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
            $this->sendMessage("Error updating database: {$e->getMessage()}");
        }
    }
}

        // $code = $request->query('code');
        // $state = $request->query('state'); // user token. 

        // echo PHP_EOL.$state;
        // if (!$code) {
        //     return response()->json(['error' => 'No code provided'], 400);
        // }

        // // Exchange code for access token
        // $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
        //     'grant_type' => 'authorization_code',
        //     'code' => $code,
        //     'redirect_uri' => config('discord.ngrok') . '/api/spotify-auth-callback',
        //     'client_id' => config('app.spotify_client_id'),
        //     'client_secret' => config('app.spotify_client_secret'),
        // ]);

        // if ($response->failed()) {
        //     Log::error('Spotify token exchange failed', ['response' => $response->body()]);
        //     return response()->json(['error' => 'Token exchange failed'], 500);
        // }

        // $data = $response->json();