<?php

namespace App\Services;


use App\Jobs\DeactivateTransfer;
use App\Models\CustomerUser;
use App\Models\DailyRequestTransfer;
use App\Models\MessageTelegram;
use App\Models\RequestTransfer;
use App\Models\Setting;
use App\Models\SupportTelegram;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Models\UserTradeAccess;
use App\Models\WordTelegram;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Telegram\Bot\FileUpload\InputFile;

class SupportServices extends TextServices
{

    public function __construct($token)
    {
        parent::__construct($token);
    }

    public function supportByMessage()
    {
        SupportTelegram::create([
            "update_id" => data_get($this->update, '$this->update_id'),
            "message_id" => data_get($this->update, 'message.message_id'),
            "user_telegram_id" => $this->getUserId(),
            "text" => data_get($this->update, 'message.text'),
            "data" => json_encode($this->update)
        ]);

        $this->telegram_services->sendMessage($this->getUserId(), "پیام شما دریافت شد پس از بررسی با شما تماس حاصل می شود ");

    }
}
