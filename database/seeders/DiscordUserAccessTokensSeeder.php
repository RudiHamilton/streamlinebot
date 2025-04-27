<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscordUserAccessTokensSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('discord_user_access_tokens')->insert([
            'username' => '1rudi.',
            'discord_id' => '495176229159305218',
            'bot_access_token' => 'RbXJ4td0b70lB5R2tfONfkFNtNvkipsf3I2foM7Sdc6000b5',
        ]);

    }
}
