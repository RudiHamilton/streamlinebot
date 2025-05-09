<?php

namespace App\Commands;

use App\Services\QueueService;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class Queue extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'queue';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Queue command.';

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        $queue = QueueService::getQueue();
   
        if (empty($queue)) {
            return $this->message()
                ->title('Queue is empty')
                ->content('There are currently no songs in the queue.')
                ->error()
                ->send($message);
        }
        if (count($queue) == 1){
            return $this->message()
                ->title('Only one item in queue')
                ->content('There is only one song in the queue. Please add more to view')
                ->error()
                ->send($message);
        }

        return $this->buildQueuePage(1)->send($message);

    }
    
    // Static property to maintain page state between interactions
    private static int $currentPage = 1;
    
    private function buildQueuePage($page = null)
    {
        // Use the provided page number or fall back to the static property
        if ($page !== null) {
            self::$currentPage = $page;
        }
        
        $queue = QueueService::getQueue();
        $perPage = 10;
        $totalPages = max(1, (int) ceil(count($queue) / $perPage)); // get the total pages, min 1
        
        // Ensure the current page is within valid bounds
        self::$currentPage = max(1, min(self::$currentPage, $totalPages));
        
        var_dump("Queue pagination: Building page " . self::$currentPage . " of {$totalPages}");
        
        $offset = (self::$currentPage - 1) * $perPage;
        $pageTracks = array_slice($queue, $offset, $perPage);
    
        $msg = $this->message()
            ->title("Current Queue (Page " . self::$currentPage . "/{$totalPages})");
    
        foreach ($pageTracks as $index => $track) {
            $trackNum = $offset + $index + 1;
            $artist = is_array($track['artists']) ? implode(', ', $track['artists']) : $track['artists'];
    
            $msg->field(
                "Track #{$trackNum}",
                "**[{$track['song']}]({$track['url']})**\nBy: {$artist}\n**Duration: {$track['duration']}**",
                false
            );
        }
    
        // Create pagination buttons that simply increment or decrement the static page
        if ($totalPages > 1) {
            $msg->clearButtons();
            
            if (self::$currentPage > 1) {
                $msg->button(label: "< Previous", route: "queue:prev", style: 'secondary');
            }
            
            if (self::$currentPage < $totalPages) {
                $msg->button(label: "Next >", route: "queue:next", style: 'secondary');
            }
        }
    
        return $msg;
    }
    
    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'queue:prev' => function (Interaction $interaction) {
                self::$currentPage--; // Decrement the page (bounds checking happens in buildQueuePage)
                var_dump("Queue navigation: Moving to previous page, now " . self::$currentPage);
                return $this->buildQueuePage()->edit($interaction);
            },
            
            'queue:next' => function (Interaction $interaction) {
                self::$currentPage++; // Increment the page (bounds checking happens in buildQueuePage)
                var_dump("Queue navigation: Moving to next page, now " . self::$currentPage);
                return $this->buildQueuePage()->edit($interaction);
            },
        ];
    }
}