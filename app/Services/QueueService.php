<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\SearchService;
use YtdlpService;

Class QueueService
{
    public static $Queue = []; 

    public static function addToQueue(array $track): void
    {
        self::$Queue[] = $track;
    }

    public static function getQueue(): array
    {
        return self::$Queue;
    }

    public static function clearQueue(): void
    {
        self::$Queue = [];
    }

    public static function removeTrackByIndex(int $index): void
    {
        if (isset(self::$Queue[$index])) {
            unset(self::$Queue[$index]);
            self::$Queue = array_values(self::$Queue); // Reindex
        }
    }

    public static function skipTrack(): ?array
    {
        return array_shift(self::$Queue); // Removes and returns first track PROB A BETTER WAY HANDLE THIS FOR GOING BACK SONGS
    }

    public static function insertAtTop(array $track): void
    {
        array_unshift(self::$Queue, $track);
    }

    public static function getNextTrack(): ?array
    {
        return self::$Queue[0] ?? null;
    }

    public static function shuffleQueue(): void
    {
        shuffle(self::$Queue);
    }

    public function getUsersSpotifyQueue($spotifyAppToken)
    {

        $ytdlpService = new YtdlpService;

        $queue = Http::withHeaders([
            'Authorization' => 'Bearer ' . $spotifyAppToken,
        ])->get('https://api.spotify.com/v1/me/player/queue');
            
        $data = $queue->json();

        if (empty($data['queue'])){
            return null;
        }   

        $spotifyCurrentlyPlaying = $data['currently_playing'];
        $currentArtists = $spotifyCurrentlyPlaying['artists'];
        $urls = $spotifyCurrentlyPlaying['external_urls'];
        $currentUrl = $urls['spotify'];

        $currentDuration = $this->msToMins($spotifyCurrentlyPlaying['duration_ms']);

        foreach($currentArtists as $currentArtist){
            $currentArtistNames[] = $currentArtist['name'];
        }

        $currentSongName = $spotifyCurrentlyPlaying['name'];

  
        $query = $currentSongName.$currentArtistNames[0];
        $url = $ytdlpService->search($query,'spotify');

        $spotifyQueue = $data['queue'];

        $currentTrack = [
            'song' => $currentSongName,
            'artists' => $currentArtistNames,
            'url' => $url,
            'duration' => $currentDuration,
            'source' => 'spotify',
            'processed' => true,
        ];

        QueueService::addToQueue(track: $currentTrack);
        
        for($x = 0; $x <= 18; $x++) {  
            $song = $spotifyQueue[$x];
            $urls = $song['external_urls'];
            $songName = $song['name'];
            $artists = $song['artists'];
            $url = $urls['spotify'];
            $duration = $this->msToMins($song['duration_ms']);

            
            
            $artistNames = [];
            foreach($artists as $artist) {
                $artistNames[] = $artist['name'];
            }
            
            $query = $songName.$artistNames[0];

            $track = [
                'song' => $songName,
                'artists' => $artistNames,
                'url' => $url,
                'duration' => $duration,
                'source' => 'spotify',
                'processed' => false,
            ];

            QueueService::addToQueue(track: $track);
            
        }
        return QueueService::getQueue();    
    }

    public function processUnprocessedTracks(): void
    {
        $ytdlpService = new YtdlpService;
        foreach (self::$Queue as $index => &$track) {
            if (!empty($track['processed'])) {
                continue;
            }

            $query = $track['song'] . $track['artists'][0] ?? '';
            $backupQuery1 = $track['song'] . ' ' . $track['artists'][0] ?? '';
            $backupQuery2 =   $track['artists'][0] . ' ' . $track['song']?? '';
            $backupQuery3 = '"'. $track['song'] . $track['artists'][0].'"' ?? '';
            $backupQuery4 = '"'.  $track['artists'][0] . $track['song'].'"' ?? '';

            $ytUrlData = $ytdlpService->search($query, 'spotify');
            
            if (empty($ytUrlData['url'])){
                $ytUrlData = $ytdlpService->search($backupQuery1, 'spotify');
            }
            if (empty($ytUrlData['url'])){
                $ytUrlData = $ytdlpService->search($backupQuery2, 'spotify');
            }
            if (empty($ytUrlData['url'])){
                $ytUrlData = $ytdlpService->search($backupQuery3, 'spotify');
            }
            if (empty($ytUrlData['url'])){
                $ytUrlData = $ytdlpService->search($backupQuery4, 'spotify');
            }
            if (empty($ytUrlData['url'])){
                $track['song'] = $track['song']. ' Is broken and we cannot find a link';
            }
            $track['url'] = $ytUrlData['url'] ?? '';
            $track['processed'] = true;
        }

        // Overwrite with updated tracks
        self::$Queue = array_values(self::$Queue);
    }


    public static function msToMins($duration) {
        // Convert milliseconds to seconds first
        $totalSeconds = floor($duration / 1000);
        
        // Calculate minutes and remaining seconds
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        
        // Calculate remaining milliseconds
        $milliseconds = $duration % 1000;
        
        $output = "";
        
        // Add minutes if available
        if ($minutes > 0) {
            $output = "$minutes minutes ";
        }
        
        // Add seconds if available
        if ($seconds > 0 || ($minutes == 0 && $milliseconds == 0)) {
            $output .= "$seconds seconds";
        }
        
        return $output;
    }

}