<?php

namespace App\Services;

use Laracord\Services\Service;

class QueueProcessor extends Service
{
    /**
     * The service interval.
     */
    protected int $interval = 100;

    /**
     * Handle the service.
     */

     //IDEA SO IF QUEUE INDEX = 8-9 WHEN PROCESSING COULD ALSO SEND OUT REQUEST TO NEW QUEUE AND THEN PROCESS THEN RETURN QUEUE 
    public function handle(): void
    {
        $queue = new QueueService;

        // Check if there are any unprocessed tracks
        $unprocessedTracks = array_filter($queue::getQueue(), function($track) {
            return empty($track['processed']);
        });

        // If there are no unprocessed tracks, set the interval to 2 minutes (120 seconds)
        if (empty($unprocessedTracks)) {
            $this->interval = 180;
            $this->console()->log('No unprocessed tracks found. Setting interval to 2 minutes.');
        } else {
            $queue->processUnprocessedTracks();
            $this->console()->log('Running queue service with the default interval.');
        }

        // Here, you would schedule the next run of this service with the updated interval.
        $this->scheduleNextRun();
    }

    /**
     * Schedule the next run based on the current interval.
     */
    private function scheduleNextRun(): void
    {
        $this->console()->log("Next run scheduled in {$this->interval} seconds.");
    }
}
