<?php

namespace App\Http;

use Illuminate\Http\Request;
use Laracord\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;

class StreamlineApiController extends Controller
{
    //will return the users query to a json that will hold 2 values.
    public function search(Request $request, SearchService $service)
    {
        dd('we made it?');
        $args = $request->input('args'); // expects array
        $data = $service->searchSanitisation($args); // takes the argument and determines what type of argument it is: soundcloud/youtbe/text query.

        return response()->json($data);
   
    }
}
