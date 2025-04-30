<?php

namespace App\Http;

use App\Models\DiscordUserAccessTokens;
use CreateSpotifyToken;
use Illuminate\Http\Request;
use Laracord\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\CharacterStream;
use React\Promise\Promise;

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
        PHP_EOL.var_dump($state);
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

        // return new Promise(function ($resolve) use ($code, $state, $data) {
        //     DiscordUserAccessTokens::where('bot_access_token', $state)->update([
        //         'spotify_auth_token' => $code,
        //         'spotify_app_token' => $data['access_token'],
        //         'spotify_app_refresh_token' => $data['refresh_token'],
        //         'spotify_expires_at' => $data['expires_in'],
        //     ]);
        //     $resolve(redirect('https://open.spotify.com/'));
        // });

        return redirect('https://open.spotify.com/');

        //(new CreateSpotifyToken())->storeSpotifyAccessTokens(bot_access_token: $state, spotify_auth_token: $code, spotify_app_token: $data['access_token'], spotify_app_refresh_token: $data['refresh_token'],spotify_expires_at: $data['expires_in']);

       
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