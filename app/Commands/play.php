<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;
use App\Services\SearchService;
use App\Services\UserAuthService;
use Illuminate\Support\Facades\Http;

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

        $userId = $message->user_id;
        $usertoken = $userAuthService->tokenCheck($userId);

        //checks to see if there is an argument following the play. for future might have a else to this to unpause a song
        if (empty($args)) {
            return $this->message()
                ->title('Error')
                ->content('Please provide a URL from soundcloud/youtube/spotify or a song name.')
                ->send($message);
        }

        // will check the types of urls and return the string to an api that the microservices will use to determine what website to get the song from.
        $response = Http::post(config('discord.http').'/api/search-audio/?token='.$usertoken, [
            'args' => $args
        ]);
        
        $data = $response->json();

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
