<?php

use Symfony\Component\Process\Process;

Class YtdlpService
{

    public function buildProcess(string $query)
    {
        return new Process([
            'yt-dlp',
            'ytsearch1:'.$query,
            '--dump-json',
            '--default-search',
            'ytsearch',
            '--no-playlist',
            '--no-check-certificate',
            '--geo-bypass',
            '--flat-playlist',
            '--skip-download',
            '--quiet',
        ]);
    }

    public function search($query,$source)
    {
        // execute command and collect output
        $process = $this->buildProcess($query);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('yt-dlp failed: '.$process->getErrorOutput());
        }
        if (empty($process->getOutput())){
            return 'failed';
        }
        
        return $this->parseResult($process->getOutput(),$source);
    }

    public function parseResult($json, $source)
    {
        $arrayOutput = json_decode($json, true);

        // channels wont return a duration so can use this to figure out if artist is channel or song.
        if (empty($arrayOutput['duration'])) {
            return null;
        }

        $title = $arrayOutput['title'];
        
        // if artist is from a (artist) - Topic rtrim will remove the - Topic e.g. ACDC - Topic becomes ACDC
        $artist = rtrim($arrayOutput['channel'], '- Topic');

        // calling secondsToMins function
        $duration = $this->secondsToMins($arrayOutput['duration']);

        $url = $arrayOutput['url'];
        echo $title.PHP_EOL;
        echo 'Artist: '.$artist.PHP_EOL;
        echo 'Duration: '.$duration.PHP_EOL;
        
        return $track = [
            'song' => $title,
            'artists' => $artist,
            'duration' => $duration,
            'url' => $url,
            'source' => $source,
            'processed' => true,
        ];
        
    }

    // public function rawJson(string $query): ?string
    // {
    //     $process = $this->buildProcess($query);
    //     $process->run();

    //     return $process->isSuccessful() ? $process->getOutput() : null;
    // }

    // just to display minutes and seconds nicely self explainitory
    public function secondsToMins($duration)
    {
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        $minutesoverall = 0;
        if ($minutes > 0) {
            $minutesoverall = "$minutes minutes ";
        }
        if ($seconds >= 0) {
            $secondsoverall = "$seconds seconds.";
        }
        $duration = $minutesoverall.$secondsoverall;

        return $duration;
    }
}