<?php

namespace App\Services;


use App\Models\Transfer;

class ActionServices
{
    public static function requestTransfer($data)
    {
        $array = str_replace('request_transfer_', '', $data);
        $info = explode("_", $array);
        $id = data_get($info, 0);
        $num = data_get($info, 1);
        $transfer = Transfer::find($id);
        if ($transfer) {
            try {
                if ($transfer->number >= $num)
                    $transfer->number -= $num;
                $keyboard["inline_keyboard"] = self::getKeyboardRequest( $transfer);


                $telegram_services->editMessageReplyMarkup($user_id, $transfer->message_id, $keyboard);
                $transfer->update();
            } catch (\Exception $e) {

                logger("exp",[$e->getMessage(),$e->getLine()]);
            }
        }
    }

    /**
     * @param mixed $number
     * @param \Illuminate\Database\Eloquent\Model|Transfer $transfer_new
     * @return mixed
     */
    public static function getKeyboardRequest( Transfer $transfer_new): mixed
    {
        $m = 0;
        $k = 0;
        $number = $transfer_new->number;
        $keyboard = [];
        for ($i = 1; $i <= $number; $i++) {
            $keyboard[$k][$m++] = [
                'text' => $i,
                'callback_data' => "request_transfer_" . $transfer_new->id . "_" . $i,
            ];
            if ($m == 3) {
                $m = 0;
                $k++;
            }
        }
        if(!$keyboard)
            $keyboard = new \stdClass();
        logger("key",[$keyboard]);
        return $keyboard;
    }
}
