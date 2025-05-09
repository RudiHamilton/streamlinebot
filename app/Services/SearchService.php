<?php

namespace App\Services;

use App\Models\DiscordUserAccessTokens;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchService
{

    //used to santise the argument passed in from the user to determine if its a link, what type of link and if its a text query
    public function searchSanitisation($args,$discordId)
    {
        //vars used to check what the query is
        $soundcloud = 'soundcloud.com';
        $spotify = 'open.spotify.com';
        $spotifyPlaylist = 'open.spotify.com/playlist';
        $spotifyTrack = 'open.spotify.com/track';
        //https://open.spotify.com/playlist/6wXzcvW2rvYUYopSry4FUc?si=e53227dd68424bac
       
        $youtubeWatch = 'www.youtube.com';
        $youtubeShare = 'youtu.be';
    
        //defaults
        $parsedHost = null;
        $userQuery = $args[0];

        //if the argument starts with https its a link. 
        if (str_starts_with($args[0],'https') == 'https'){
            $url = parse_url($args[0]);
            $parsedHost = $url['host'];

            $parsedPath = $url['path'];
            if (strpos($parsedPath, '/') !== false) {
                $parts = explode('/', $parsedPath);
                $trimmedPath = trim($parts[1]); // take the second half after '|'
                $id = $parts[2];
            }
        }
        
        //if i want to add more urls just add else if and handle it on js end.
        if ($parsedHost == $soundcloud){
            $websiteUsed = 'soundcloud';
        }
        elseif($parsedHost == $youtubeWatch || $parsedHost == $youtubeShare){
            $websiteUsed = 'youtube';
        }
        elseif($parsedHost == $spotify){
            //db query goes here to check if user exists in db if not then will use my token ig :/
            $websiteUsed = 'spotify';
            $createSpotifyToken = new CreateSpotifyToken;
            $createSpotifyToken->checkSpotifyAppToken($discordId);
            $spotify_access_token = DiscordUserAccessTokens::where('discord_id', $discordId)->value('spotify_app_token');
            
            if (empty($spotify_access_token)){
                $spotify_access_token = DiscordUserAccessTokens::where('discord_id',config('discord.botid'))->value('spotify_app_token');
            }

            if($trimmedPath == 'track'){
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$spotify_access_token,
                ])->get('https://api.spotify.com/v1/tracks/'.$id);

                $song = $response->json();

                $songName = $song['name'];
                $artists = $song['artists'];
                $artistNames = [];
                foreach($artists as $artist) {
                    $artistNames[] = $artist['name'];
                }
                $userQuery = $songName.' '.$artistNames[0];

            }elseif($trimmedPath == 'playlist'){

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$spotify_access_token,
                ])->get('https://api.spotify.com/v1/playlists/'.$id);

                $playlist = $response->json();
                $tracks = $playlist['tracks'];
                $tracks = $tracks['items'];
              
                foreach($tracks as $track){
                    $song = $track['track'];
                    $songName = $song['name'];
                    $artists = $song['artists'];
                    $artistNames = [];
                    $duration = QueueService::msToMins($song['duration_ms']);
                    $urls = $song['external_urls'];
                    $url = $urls['spotify'];
                    foreach($artists as $artist) {
                        $artistNames[] = $artist['name'];
                    }
                    $track = [
                        'song' => $songName,
                        'artists' => $artistNames,
                        'url' => $url,
                        'duration' => $duration,
                        'source' => 'spotify',
                        'processed' => false,
                    ];
                    QueueService::addToQueue($track);
                    
                }
                return 'PlaylistSuccess';
            }else{
                return 'we cannot play podcasts, videos or audiobooks from spotify yet.';
            }
        }
        else{
            //if its a query thats user typed and not a url use this. Will parse this later due to the way laracord handles args
            //into an array so will take this and know that the args will have to be imploded into a user search.
            $websiteUsed = 'youtube.query';
            $userQuery = implode(' ',$args);
        }
        //if a url will be the url as user-query if a string query will be a string query;
        return [
            'website-used' => $websiteUsed, 
            'user-query' => $userQuery,
        ];
    }
}