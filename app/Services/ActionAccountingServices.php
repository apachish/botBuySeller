<?php

namespace App\Services;


use App\Jobs\CancelOrder;
use App\Jobs\CancelOrderAccounting;
use App\Models\Bot;
use App\Models\BotMenuUser;
use App\Models\RequestTransfer;
use App\Models\Transfer;
use App\Models\UserTelegram;
use App\Services\Admin\CustomerServices;
use App\Services\Admin\SettingServices;
use App\Services\Admin\TimeServices;
use App\Services\Admin\TransactionServices;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;


class ActionAccountingServices extends TextServices
{

    public $keyboard_menu = [
        [
            ["text" => "\xF0\x9F\x94\x8Dجستجو"],
            ['text' => "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله"]
        ],
    ];

    public function __construct($token)
    {
        parent::__construct($token);
    }


    public function checkMessage()
    {

        $access_text = [
            "/start",
            "\xF0\x9F\x94\x8Dجستجو",
            "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله",
        ];
        if (in_array($this->message, $access_text))
            return true;

        return false;
    }

    public function actionData()
    {
        if (str_contains($this->getData(), "get_report_")) {
            $id = str_replace('get_report_', '', $this->getData());
            if ($id) {
                $message = "تاریخ را بصورت زیر وارد کنید";
                $message .= "   1403/03/09 ";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), "get_report_" . $id);

            }

        } elseif (str_contains($this->getData(), "no_request_transfer_")) {
            $id = str_replace('no_request_transfer_', '', $this->getData());
            $message_id = cache()->get("cancel_number_transaction_" . $id);
            $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $message_id, new \stdClass());
        } elseif (str_contains($this->getData(), "cancel_request_transfer_")) {
            $id = str_replace('cancel_request_transfer_', '', $this->getData());

            $order = RequestTransfer::with(["userRequest.customer", "transferReport", "dailyRequest"])->find($id);
            if ($order) {
                dispatch(new CancelOrder($id));
                dispatch(new CancelOrderAccounting($id));

//                $transfer = $order->transferReport;
//
//                if (data_get($order, "userRequest.role") == "customer") {
//                    $transaction_party_req = "مشاهده فقط برای سرگروه";
//                    $transaction_party_reqs = data_get($order, "userRequest.fullName") . "(" . data_get($order, "userRequest.customer.fullName") . ")";
//                } else
//                    $transaction_party_req = data_get($order, "userRequest.fullName");
//
//                if (data_get($order, "transferReport.user.role") == "customer") {
//                    $transaction_party = "مشاهده فقط برای سرگروه";
//                    $transaction_partys = data_get($order, "transferReport.user.fullName") . "(" . data_get($order, "transferReport.user.customer.fullName") . ")";
//                } else
//                    $transaction_party = data_get($order, "transferReport.user.fullName");
//
//
//                /*
//                 * message for req and head
//                 */
//                $message = $this->getStr(1, $transfer, $order, $transaction_party);
//                $this->sendBotWord(data_get($order, "userRequest.telegram_id"), $message);
//
//                if (data_get($order, "userRequest.role") == "customer") {
//                    $message = $this->getStr(1, $transfer, $order, $transaction_partys);
//                    $this->sendBotCustomer(data_get($order, "userRequest.customer.telegram_id"), $message);
//                }
//
//                /*
//             * message for transfer and head
//             */
//                $message = $this->getStr(2, $transfer, $order, $transaction_party_req);
//
//                $this->sendBotWord(data_get($order, "transferReport.user.telegram_id"), $message);
//                if (data_get($order, "transferReport.user.role") == "customer") {
//                    $message = $this->getStr(2, $transfer, $order, $transaction_party_reqs);
//                    $this->sendBotCustomer(data_get($order, "transferReport.user.customer.telegram_id"), $message);
//                }

                $this->getTelegramServices()->sendMessage($this->getUserId(), "کنسل شد پیغام برای مشتریان و سرگروه ارسال شد");
//
//                $order->dailyRequest->delete();
//                $order->delete();
                $message_id = cache()->get("cancel_number_transaction_" . $order->id);
                $this->telegram_services->editMessageReplyMarkup($this->getUserId(), $message_id, new \stdClass());

            } else {
                $this->getTelegramServices()->sendMessage($this->getUserId(), "موفق به کنسل کردن معامله نشد");

            }
        }

    }

    private function sendBotCustomer($chat_id, array|string $message): void
    {
        $bot_customer = Bot::where("title", "botCustomer")->first();
        if ($bot_customer) {
            try {
                $telegram_customer = new Api($bot_customer->token);
                $telegram_customer->sendMessage(
                    [
                        'chat_id' => $chat_id,
                        'text' => $message,
                    ]
                );
            }catch (\Exception $exception){
                logger("get error",[
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getTrace(),
                    $exception->getFile()
                ]);
            }
        }
    }

    private function qsendBotWord($chat_id, array|string $message): void
    {
        $bot_user = Bot::where("title", "botUser")->first();
        if ($bot_user) {
            try {
                $telegram_customer = new Api($bot_user->token);
                $send_user = $telegram_customer->sendMessage(
                    [
                        'chat_id' => $chat_id,
                        'text' => $message,
                    ]
                );

            }catch (\Exception $exception){
                    logger("get error",[
                        $exception->getMessage(),
                        $exception->getLine(),
                        $exception->getCode(),
                        $exception->getTrace(),
                        $exception->getFile()
                    ]);
                }
        }
    }

    public function actionText()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        switch ($this->getMessage()) {
            case "\xF0\x9F\x94\x8D\xF0\x9F\x93\x83جستجو با حواله":
                $this->getTelegramServices()->sendMessage($this->getUserId(), "شماره حواله مربوطه را وارد کنید");
                cache()->set($this->getKeyCache() . $this->getUserId(), "set_number_transaction");
                break;
            case "\xF0\x9F\x94\x8Dجستجو":
                $message = "\n\n";
                $message .= "شماره موبایل یا نام شخص را وارد کنید";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), "search_customer");
                break;
            case  "/start":
                $this->getTelegramServices()->sendMessage($this->getUserId(), "خوش آمدید");
                break;
        }
    }

    public function actionTextCache()
    {
        $key_case = $this->getMessageCache();
        if (str_contains($this->getMessageCache(), "get_report_"))
            $key_case = "get_report_";


        switch ($key_case) {

            case "set_number_transaction":
                $order_buy = RequestTransfer::with(["userRequest.customer", "transferReport"])->find($this->getMessage());
                if ($order_buy) {
                    $message = $this->getfactor($order_buy);
                    $keyboard[0][0] = [
                        'text' => "کنسل کردن",
                        'callback_data' => "cancel_request_transfer_" . $order_buy->id,
                    ];
                    $keyboard[0][1] = [
                        'text' => "لغو ",
                        'callback_data' => "no_request_transfer_" . $order_buy->id,
                    ];
                    $message_result = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard, false);
                    cache()->set("cancel_number_transaction_" . $order_buy->id, $message_result);
                    cache()->forget($this->getKeyCache() . $this->getUserId());
                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "یافت نشد");
                }
                break;
            case "search_customer":
                $users = UserTelegram::query();
                $users->where(function ($query) {
                    $query->where("fullName", "like", "%" . $this->getMessage() . "%");
                    $query->orWhere("mobile", "like", "%" . $this->getMessage() . "%");
                });
                $users = $users->get();
                $i = 0;
                if ($users->count()) {
                    $text = "از میان همکاران و مشتریان زیر  را انتخاب کنید";
                    foreach ($users as $user)
                        $keyboard[$i++][] = ['text' => $user->fullName, "callback_data" => "get_report_" . $user->id];

                    $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
                    cache()->set("set_head_done_" . $this->getUserId(), $menu);
                    cache()->forget($this->getKeyCache() . $this->getUserId());

                } else {
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "کاربری یافت نشد ");

                }
                break;
            case "get_report_":
                $id = str_replace('get_report_', '', $this->getMessageCache());
                $customer = UserTelegram::find($id);
                if (!isValidShamsiDate($this->getMessage()))
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "فرمت تاریخ دست نمی باشد");
                elseif ($customer) {
                    $date = $this->getMessage();
                    $date = toGregorian($this->getMessage(), "Y/m/d");

                    $date_p = toJalali($date, "Y_m_d");

                    $me = RequestTransfer::with(["transferReport" => function ($query) use ($customer, $date) {
                        $query->where("user_id", $customer->id);
                        $query->whereDate("date", $date);
                        $query->with("user");
                    }, "userRequest"])
                        ->whereHas("transferReport", function ($query) use ($customer, $date) {
                            $query->where("user_id", $customer->id);
                            $query->whereDate("date", $date);
                        })->get();
                    $request = RequestTransfer::where("request_id", $customer->id)
                        ->with(["transferReport" => function ($query) use ($date) {
                            $query->with("user");
                            $query->whereDate("date", $date);
                        }, "userRequest"])
                        ->whereHas("transferReport", function ($query) use ($date) {
                            $query->whereDate("date", $date);
                        })->get();

                    $request_transfer = [];
                    foreach ($me as $req) {
                        $request_transfer[] = $req;
                        if (data_get($customer, 'id') == data_get($req, 'transferReport.user_id')) {
                            $req->type_label = data_get($req, "type") == "buy" ? "sell" : "buy";
                            if (data_get($req, "userRequest.customer"))
                                $req->said = data_get($req, "userRequest.fullName") . "(" . data_get($req, "userRequest.customer.fullName") . ")";
                            else
                                $req->said = data_get($req, "userRequest.fullName");
                            $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";
                        } else {
                            $req->type_label = data_get($req, "type");
                            if (data_get($req, "transferReport.user.customer"))
                                $req->said = data_get($req, "transferReport.user.fullName") . "(" . data_get($req, "transferReport.user.customer.fullName") . ")";
                            else
                                $req->said = data_get($req, "transferReport.user.fullName");
                            $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";

                        }
                    }
                    foreach ($request as $req) {
                        $request_transfer[] = $req;
                        if (data_get($customer, 'id') == data_get($req, 'transferReport.user_id')) {
                            $req->type_label = data_get($req, "type") == "buy" ? "sell" : "buy";
                            if (data_get($req, "userRequest.customer"))
                                $req->said = data_get($req, "userRequest.fullName") . "(" . data_get($req, "userRequest.customer.fullName") . ")";
                            else
                                $req->said = data_get($req, "userRequest.fullName");
                            $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";

                        } else {

                            $req->type_label = data_get($req, "type");
                            if (data_get($req, "transferReport.user.customer"))
                                $req->said = data_get($req, "transferReport.user.fullName") . "(" . data_get($req, "transferReport.user.customer.fullName") . ")";
                            else
                                $req->said = data_get($req, "transferReport.user.fullName");
                            $req->color = $req->type_label == "sell" ? "#ef4444" : "dodgerblue";
                        }
                    }
                    if ($request_transfer) {
                        $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path("tmp")]);
                        $html = view('users.report_pdf', compact('date_p', 'request_transfer', 'customer'))->render();
                        $mpdf->WriteHTML($html);
                        $name_file = $this->getUserId() . "_" . $date_p . ".pdf";
                        $path = "app/public/report/" . $this->getUserId() . "/";
                        makeDirectoryStorage($path);
                        $path_report = storage_path($path . $name_file);
                        $document = $mpdf->Output($path_report, 'F');
                        $response = $this->telegram->sendDocument([
                            'chat_id' => $this->getUserId(),
                            'document' => InputFile::create($path_report, "$date_p.pdf")
                        ]);
                    } else {
                        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => 'معامله ای در این تاریخ انجام نشده']);
                    }
                } else
                    $this->getTelegramServices()->sendMessage($this->getUserId(), "کاربری یافت نشد ");

                break;

        }
    }


    public function setHeadCustomer($object)
    {
        $data = str_replace('set_head_done_', '', $object->getData());

        $array = explode("_", $data);
        $parent = (int)data_get($array, 0);
        $role = data_get($array, 1);
        $id = (int)data_get($array, 2);
        $page = (int)data_get($array, 3);
        $filter = data_get($array, 4, null);
        $user_con = UserTelegram::find($id);

        if ($user_con) {
            $user_con["agent_id"] = $parent;
            $user_con->update();
            $message_id = cache()->get("menu_List_user_" . $object->getUserId());
            $this->listUser($role, $object, $page, $message_id, $filter);
            $action_id = cache()->get("set_head_done_" . $object->getUserId());
            $object->getTelegramServices()->deleteMessage($object->getUserId(), $action_id);
            $message = "همکار";
            $message .= "\n\n";
            $user_parent = UserTelegram::find($parent);
            $message .= $user_parent->fullName;
            $message .= " برای مشتری ";
            $message .= $user_con->fullName;
            $message .= " انتخاب شد ";
            $object->getTelegramServices()->sendMessage($object->getUserId(), $message);

        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $order_buy
     * @return string
     */
    public function getfactor(\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $order_buy): string
    {
        $message = "شماره حواله:" . data_get($order_buy, 'id');
        $message .= "\n\n";
        $message .= "فی:";
        $message .= number_format(data_get($order_buy, 'price'), 0);
        $message .= "\n\n";
        $type = data_get($order_buy, "type");
        if ($type == "sell") {
            $title_request = "فروشنده";
            $title_mal = "خریدار";
        } else {
            $title_request = "خریدار";
            $title_mal = "فروشنده";

        }
        $transfer = $order_buy->transferReport;
        if (data_get($order_buy, "userRequest.role") == "customer")
            $message .= "  $title_request: " . data_get($order_buy, "userRequest.fullName") . "(" . data_get($order_buy, "userRequest.customer.fullName") . ")";
        else
            $message .= "  $title_request: " . data_get($order_buy, "userRequest.fullName");
        $message .= "\n\n";
        if (data_get($order_buy, "transferReport.user.role") == "customer")
            $message .= "  $title_mal: " . data_get($order_buy, "transferReport.user.fullName") . "(" . data_get($order_buy, "transferReport.user.customer.fullName") . ")";
        else
            $message .= "  $title_mal: " . data_get($order_buy, "transferReport.user.fullName");
        $message .= "\n\n";
        $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
        $message .= "\n\n";
        $message .= "ساعت:" . toJalali($order_buy->created_at, "H:i:s");
        $message .= "\n\n";
        $message .= "مقدار:" . data_get($order_buy, "number") . "کیلو";
        $message .= "\n\n";
        $message .= "نوع:" . getTypeTransfer($transfer->type);

        if (data_get($transfer,'description')) {
            $message .= "\n";
            $message .= "توضیحات";
            $message .= "\xE2\x9D\x97 : \n" . data_get($transfer,'description');
        }
        return $message;
    }

    /**
     * @param mixed $transfer
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $order
     * @param mixed $transaction_party_req
     * @return string
     */
    public function getStr($type, mixed $transfer, \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array $order, mixed $transaction_party_req): string
    {
        $message = "\xE2\x9D\x8C\xE2\x9D\x97";
        $message .= "حذف معامله زیر توسط ادمین به درخواست طرفبن معامله";
        $message .= "\xE2\x9D\x8C\xE2\x9D\x97";
        $message .= "\n\n";
        $message .= $type == 1 ? $transfer->message_request_me : $transfer->message_request;
        $message .= "\n\n";
        $message .= "مقدار:" . data_get($order, "number") . "کیلو";
        $message .= "\n\n";
        $message .= "نوع:" . getTypeTransfer($transfer->type);
        if ($transfer->description) {
            $message .= "\n\n";
            $message .= "توضیحات";
            $message .= "\xE2\x9D\x97 : \n\n" . $transfer->description;
        }
        $message .= "\n\n";
        $message .= "طرف معامله:" . $transaction_party_req;
        $message .= "\n\n";
        $message .= "برای:" . toJalali($transfer->date, "Y/m/d");
        $message .= "\n\n";
        $message .= "       شماره حواله:" . data_get($order, 'id');
        return $message;
    }

}
