<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Services\TelegramServices;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class TestApiTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-api-telegram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

    }
}
