<?php

namespace App\Commands;

use App\Models\DiscordUserAccessTokens;
use App\Services\UserAuthService;
use CreateSpotifyToken;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Facades\Http;
use Laracord\Commands\Command;

class mimic extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'mimic';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Mimic command.';

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
        //gets user and voice channel
        $user = $message->member;
        $channel = $message->member->getVoiceChannel() ?? null;

        if (empty($channel)) {
            return $this->message()
                ->title('Error')
                ->content('You need to be in voice chat to use this command.')
                ->send($message);
        }

        $voice = $message->member->getVoiceChannel()->getBotPermissions();

        if ($voice['view_channel'] == false) {
            return $this->message()
                ->title('Error')
                ->content('I need permission for your channel :(

                    You might\'ve turned off my permission to join channels when I joined but just reapply these permissions and we should be ok!')
                ->send($message);
        }

        $userAuthService = new UserAuthService;
        $username = $message->member->username;
        $discordId = $message->user_id;
        $usertoken = $userAuthService->tokenCheck($username,$discordId);

        $spotifyTokenChecks = new CreateSpotifyToken;

        $spotifyAppToken = $spotifyTokenChecks->checkSpotifyAppToken($discordId);

        if($spotifyAppToken == false){
            $this
                ->message()
                ->title('Please check your DMs')
                ->content('
                    In your DMs you should see a message from StreamlineMusicBot.
                    
                    Please view this message and follow the link to enable the mimic feature.
                ')
                ->reply($message);
                

            $clientId    = config('app.spotify_client_id');
            $redirectUri = config('discord.ngrok') . '/api/spotify-auth-callback';
            $responseType = 'code';
            $token = $usertoken;
            $scope = 'user-read-currently-playing user-read-playback-state';

            $url = "https://accounts.spotify.com/authorize?"
                    . "client_id={$clientId}"
                    . "&response_type={$responseType}"
                    . "&redirect_uri=" . urlencode($redirectUri)
                    . "&state={$token}"
                    . "&scope=" . urlencode($scope);

            echo $url;
        
            $this
                ->message()
                ->title('Would you like to like your spotify to streamline?')
                ->content('DISCLAIMER
                
                    By allowing you accept that streamline will have access to your player this includes: 

                    -View your Spotify account data

                    -Your name, username, profile picture, Spotify followers, and public playlists.

                    -Take actions in Spotify on your behalf

                    -Stream and control Spotify on your other devices

                    We just want access to your queue and playback state, we will not edit or tamper with your playback experience when not using the bot.

                    Click the link button below to get started! 
x
                    im not like that cuh (enjoy freemium 😎)
                ')
                //https://accounts.spotify.com/authorize?client_id='.config('app.spotify_client_id').'&response_type=code&redirect_uri='.$url
                ->button('Go to authorisation process 🔒',$url)
                ->sendTo($discordId);
    
        }elseif($spotifyAppToken == true){
            return $this
            ->message()
            ->title('Starting mimic')
            ->content('
            Bot is now mimicing: '
            .$username.'
            All music that is now playing from the bot is from '
            .$username
            )
            ->send($message);

        }else{
            return $this
            ->message()
            ->title('Broken pipe')
            ->content('How tf u do dis?')
            ->send($message);

        }
        
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            //
        ];
    }
}
