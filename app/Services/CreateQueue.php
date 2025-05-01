<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

Class CreateQueue
{
    public function getUsersQueue($spotifyAppToken)
    {
        $queue = Http::withHeaders([
            'Authorization' => 'Bearer ' . $spotifyAppToken,
        ])->get('https://api.spotify.com/v1/me/player/queue');
        
        $data = $queue->json();
        if ($data['queue'] == null){
            return null;
        }   

        $spotifyCurrentlyPlaying = $data['currently_playing'];
        $currentArtists = $spotifyCurrentlyPlaying['artists'];

        foreach($currentArtists as $currentArtist){
            $currentArtistNames[] = $currentArtist['name'];
        }

        $currentSongName = $spotifyCurrentlyPlaying['name'];
        $spotifyQueue = $data['queue'];
        $processedQueue [] = [
            'song' => $currentSongName,
            'artists' => $currentArtistNames,
        ];
        
        for($x = 0; $x <= 10; $x++) {
            $song = $spotifyQueue[$x];
            $songName = $song['name'];
            $artists = $song['artists'];
            
            $artistNames = [];
            foreach($artists as $artist) {
                $artistNames[] = $artist['name'];
            }
        
            $processedQueue[] = [
                'song' => $songName,
                'artists' => $artistNames,
            ];
        }    
        return $processedQueue;
    }
}