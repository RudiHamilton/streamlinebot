<?php

namespace App\Commands;

use App\Services\UserAuthService;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class pause extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'pause';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Pause command.';

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
        $channel = $message->member->getVoiceChannel() ?? null;

        if (empty($channel)) {
            return $this->message()
                ->title('Error')
                ->content('You need to be in voice chat to use this command.')
                ->error()
                ->send($message);
        }
        $voice = $message->member->getVoiceChannel()->getBotPermissions();
        if ($voice['view_channel'] == false) {
            return $this->message()
                ->title('Error')
                ->content('I need permission for your channel :(

                    You might\'ve turned off my permission to join channels when I joined but just reapply these permissions and we should be ok!')
                ->error()
                ->send($message);
        }
        $userAuthService = new UserAuthService;
        $username = $message->member->username;
        $discordId = $message->user_id;
        $usertoken = $userAuthService->tokenCheck($username,$discordId);


        return $this
            ->message()
            ->title('Pause')
            ->content('Hello world!')
            ->button('👋', route: 'wave')
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
