<?php

namespace App\Console\Commands;

use App\Models\AccessBot;
use App\Models\Bot;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;

class AddUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-user-admin';

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
        $token = $this->ask('What is  token bot?');
        $title = $this->ask('What is  title bot?');
        $bot = Bot::create(["title"=>$title,"token"=>$token]);


    }

}
