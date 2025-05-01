<?php

namespace App;

use App\Http\StreamlineApiController;
use Illuminate\Support\Facades\Route;
use Laracord\Laracord;
use Discord\Parts\User\Activity;

class Bot extends Laracord
{
    public function beforeBoot(): void
    {
        cache()->flush();
    }
    public function afterBoot(): void
    {
        $activity = $this->discord()->factory(Activity::class, [
            'type' => Activity::TYPE_PLAYING,
            'name' => 'bladee in the club 🪩',
        ]);
        $this->discord()->updatePresence($activity);
    }
    /**
     * The HTTP routes.
     */
    public function routes(): void
    {
        Route::middleware('web')->group(function () {
            // Route::get('/', fn () => 'Hello world!');
        });

        Route::middleware('api')->group(function () {
             //posts the users query.
             //get for now to test...
            Route::get('/api/search-audio',[StreamlineApiController::class,'search']);
            
        });
        Route::get('/api/spotify-auth-callback',[StreamlineApiController::class, 'spotifyAuthCallback']);
       
    }
}
