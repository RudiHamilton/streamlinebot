<?php

namespace App;

use App\Http\StreamlineApiController;
use Illuminate\Support\Facades\Route;
use Laracord\Laracord;

class Bot extends Laracord
{
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
            Route::post('/api/search-audio',[StreamlineApiController::class,'search']);
        });

       
      
    }
}
