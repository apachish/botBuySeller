<?php

namespace App\Console\Commands;

use App\Models\AccessBot;
use App\Models\Bot;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\confirm;

class AddBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-bot';

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
        $chanel_id = $this->ask('What is  chanel_id bot?');
        $bot = Bot::updateOrCreate(["token"=>$token],["title"=>$title,"chanel_id"=>$chanel_id]);

        $this->info("done");

    }

}
