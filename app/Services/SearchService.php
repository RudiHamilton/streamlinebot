<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SearchService
{

    //used to santise the argument passed in from the user to determine if its a link, what type of link and if its a text query
    public function searchSanitisation($args)
    {
        Log::info('I FUCKING made it :cry:');
        //vars used to check what the query is
        $soundcloud = 'soundcloud.com';
        $youtubeWatch = 'www.youtube.com';
        $youtubeShare = 'youtu.be';
    
        //defaults
        $parsedHost = null;
        $userQuery = $args[0];

        //if the argument starts with https its a link. 
        if (str_starts_with($args[0],'https') == 'https'){
            $url = parse_url($args[0]);
            $parsedHost = $url['host'];
        }

        //if i want to add more urls just add else if and handle it on js end.
        if ($parsedHost == $soundcloud){
            $websiteUsed = 'soundcloud';
        }
        elseif($parsedHost == $youtubeWatch || $parsedHost == $youtubeShare){
            $websiteUsed = 'youtube';
        }
        else{
            //if its a query thats user typed and not a url use this. Will parse this later due to the way laracord handles args
            //into an array so will take this and know that the args will have to be imploded into a user search.
            $websiteUsed = 'youtube.query';
            $userQuery = implode($args);
        }
        //if a url will be the url as user-query if a string query will be a string query;
        return [
            'website-used' => $websiteUsed, 
            'user-query' => $userQuery,
        ];
    }
}