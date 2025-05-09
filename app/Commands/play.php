<?php

namespace App\Commands;

use App\Services\QueueService;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;
use App\Services\SearchService;
use App\Services\UserAuthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use YtdlpService;

class play extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'play';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Play command.';

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
        if(empty(QueueService::getQueue())){
            $queueState = 'empty';
        }else{
            $queueState = 'notempty';
        }
        $userAuthService = new UserAuthService;

        //checks to see if there is an argument following the play. for future might have a else to this to unpause a song
        if (empty($args)) {
            return $this->message()
                ->title('Error')
                ->content('Please provide a URL from soundcloud/youtube/spotify or a song name.')
                ->error()
                ->send($message);
        }

        //checks to see if user is in voice channel.
        $channel = $message->member->getVoiceChannel() ?? null;

        if (empty($channel)) {
            return $this->message()
                ->title('Error')
                ->content('You need to be in voice chat to use this command.')
                ->error()
                ->send($message);
        }

        //checks what the permissions of the bot are and can it join that channel the user has called it too.
        $voice = $message->member->getVoiceChannel()->getBotPermissions();
        if ($voice['view_channel'] == false) {
            return $this->message()
                ->title('Error')
                ->content('I need permission for your channel :(

                    You might\'ve turned off my permission to join channels when I joined but just reapply these permissions and we should be ok!'
                    )
                ->error()
                ->send($message);
        }
        //FOR THIS ----------------------------------------------

        // get username and id to pass into token check
        $username = $message->member->username;
        $discordId = $message->user_id;
        $usertoken = $userAuthService->tokenCheck($username,$discordId);

        // will check the types of urls and return the string to an api that the microservices will use to determine what website to get the song from.
        //Also everything below this will run async because i dont want this bottlenecking. but on js side will wait till a user has joined call  

        $searchParamsMethod = new SearchService(); 
        $userSearchSanitised = $searchParamsMethod->searchSanitisation($args,$discordId); // pass in our users search arguments

        if($userSearchSanitised == 'PlaylistSuccess'){
            return $this->message()
            ->title(title: 'Playlist has been Queued')
            ->content('We have added your playlist to queue!'.PHP_EOL.'Use s!queue to view your songs.')
            ->send($message);
        }

        $ytdlpService = new YtdlpService();
        $track = $ytdlpService->search($userSearchSanitised['user-query'],$userSearchSanitised['website-used']);

        if ($track == 'failed'){
            return $this->message()
                ->title('Error')
                ->content('Couldnt find the song: '.$userSearchSanitised['user-query'])
                ->error()
                ->send($message);
        }
        $queue = QueueService::getQueue();
        $trackPosition = count($queue); // because it's about to be added



        QueueService::addToQueue($track);

        echo 'omg';
        // $data = $response->json();

        //TO THIS -------------------------------------------------- maybe async 

        //after these statements are finished bot can join voice channel.
        $this->discord()->joinVoiceChannel(channel: $channel, mute: false, deaf: true);
        
        $queue = QueueService::getQueue();
      
        $user = $message->member;
        
        

        if($queueState == 'empty'){
            $currentTrack = $queue[0];
            return $this
                ->message()
                ->title('Playing a song')
                ->content('
                Song: '.$currentTrack['song'].'
                Artist: '.$currentTrack['artists'].'
                Duration: '.$currentTrack['duration'].'
                Requested by: '.$user
                )
                ->send($message);
        }else{
            //IMPORTANT
            $currentTrack = $queue[$trackPosition];
            //IMPORTANT
            return $this
                ->message()
                ->title('Added song to queue')
                ->content('
                Song: '.$currentTrack['song'].'
                Artist: '.$currentTrack['artists'].'
                Duration: '.$currentTrack['duration'].'
                Requested by: '.$user
                )
                ->send($message);
        }
        //message returned after joining
        
    }
    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'wave' => fn (Interaction $interaction) => $this->message('👋')->reply($interaction), 
        ];
    }
}
