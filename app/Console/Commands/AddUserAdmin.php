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
        //
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $token = $this->ask('What is  token bot?');
        $bot = Bot::where("token",$token)->first();
        if($bot ==  null){
            $title = $this->ask('What is  title bot?');
            $bot = Bot::create(["title"=>$title,"token"=>$token]);
        }
        $user_id = $this->ask('who is  need access for bot?');
        $type = $this->ask('which type access  need for bot?');

        AccessBot::updateOrCreate([
            "bot_id"=>$bot->id,
            "user_id"=>$user_id,
            "type"=>$type
        ],[
            "bot_id"=>$bot->id,
            "user_id"=>$user_id,
            "type"=>$type
        ]);

    }
}
