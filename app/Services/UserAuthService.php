<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class UserAuthService
{
    public function tokenCheck($user_id)
    {

        


        $process = $this->createToken($user_id);
        $process->run();
        $result = $process->getOutput();

        var_dump($result);

        if(str_starts_with($result,'   ERROR') == 'ERROR'){
            $process = $this->regenerateToken($user_id);
            $process->run();
            return $result = $process->getOutput();
        }
        
        return $result;
    }
    public function createToken(int $user_id)
    {
        return new Process([
            'php',
            'laracord',
            'bot:token',
            $user_id,
        ]);
    }
    public function regenerateToken($user_id)
    {
        return new Process([
            'php',
            'laracord',
            'bot:token',
            $user_id,
            '--regenerate',
        ]);
    }
}