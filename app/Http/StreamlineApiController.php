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
        cache()->put(
            key: 'spotify_tokens_' . $state, 
            value: [
                'auth_token' => $code,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_in' => $tokenExpiresAt->addHour()->toDateTimeString(),
                'cached_at' => now(),
            ],
            ttl: now()->addMinutes(20) //if command runs every couple of minutes there is plenty of time to catch this.
        ); 
        return redirect('https://open.spotify.com/');

        //(new CreateSpotifyToken())->storeSpotifyAccessTokens(bot_access_token: $state, spotify_auth_token: $code, spotify_app_token: $data['access_token'], spotify_app_refresh_token: $data['refresh_token'],spotify_expires_at: $data['expires_in']);

       
    }
}