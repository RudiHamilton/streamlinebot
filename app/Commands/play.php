<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;
use App\Services\SearchService;
use App\Services\UserAuthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $userAuthService = new UserAuthService;

        //checks to see if there is an argument following the play. for future might have a else to this to unpause a song
        if (empty($args)) {
            return $this->message()
                ->title('Error')
                ->content('Please provide a URL from soundcloud/youtube/spotify or a song name.')
                ->send($message);
        }

        //checks to see if user is in voice channel.
        $channel = $message->member->getVoiceChannel() ?? null;

        if (empty($channel)) {
            return $this->message()
                ->title('Error')
                ->content('You need to be in voice chat to use this command.')
                ->send($message);
        }

        //checks what the permissions of the bot are and can it join that channel the user has called it too.
        $voice = $message->member->getVoiceChannel()->getBotPermissions();
        if ($voice['view_channel'] == false) {
            return $this->message()
                ->title('Error')
                ->content('I need permission for your channel :(

                    You might\'ve turned off my permission to join channels when I joined but just reapply these permissions and we should be ok!')
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
        $userSearchSanitised = $searchParamsMethod->searchSanitisation($args); // pass in our users search arguments

        //take the arguments our of the array and grab the individual values
        $websiteUsed = $userSearchSanitised['website-used'];
        $userQuery = $userSearchSanitised['user-query'];

        // points to endpoint that will be used. User token could maybe go into body.
        // $response = Http::get(config('discord.http').'/api/search-audio', [
        //     'token' => $usertoken,
        //     'website-used' => $websiteUsed,
        //     'user-query' => $userQuery,
        // ]);

        echo 'omg';
        // $data = $response->json();

        //TO THIS -------------------------------------------------- maybe async 

        //after these statements are finished bot can join voice channel.
        $this->discord()->joinVoiceChannel(channel: $channel, mute: false, deaf: true);

        //dummy placeholder
        $song = 'Invisible';
        $artist = 'Bladee';
        $duration = '3:14';
        
        $user = $message->member;

        //message returned after joining
        return $this
            ->message()
            ->title('Playing a song')
            ->content('
            Song: '.$song.'
            Artist: '.$artist.'
            Duration: '.$duration.'
            Requested by: '.$user
            )
            ->send($message);
         
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
