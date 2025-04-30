<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class help extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'help';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Help command.';

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
        return $this
            ->message()
            ->title('Help')
            ->content('
            Commands that can be used:

            s!play:  Play a song can be text or a url

            s!stop:  Stops the song and leaves voice channel

            s!pause: Pauses the track

            s!ff:    Fast forwards the song 20 seconds

            s!rr:    Rewinds the song 20 seconds
            
            s!mimic: Coming soon 🤫
            ')
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
