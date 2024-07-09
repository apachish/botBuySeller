<?php

namespace App\Jobs;

use App\Models\RequestTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $order_id;

    /**
     * Create a new job instance.
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order_buy = RequestTransfer::with(["userRequest.customer", "transferReport.user.customer","dailyRequest"])->find($this->order_id);

        logger("PartiesToTheTransaction", [$order_buy, $this->order_id]);
        if ($order_buy) {
            $transfer = $order_buy->transferReport;
            $user = data_get($order_buy, "userRequest");
            if (data_get($transfer, "user.role") == "customer")
                $colleague = data_get($transfer, "user.customer");
            else
                $colleague = data_get($transfer, "user");
            if (data_get($order_buy, "userRequest.role") == "customer") {
                $head = data_get($order_buy, "userRequest.customer");
            } else
                $head = $user;
            if ($transfer->user->role == "customer" && $user->role == "customer") {

                if (data_get($transfer, 'user.customer')) {
                    $transaction_party_req_s = data_get($transfer, 'user.fullName');
                    $transaction_party_req_s .= "(" . data_get($colleague, 'fullName') . ")";
                } else
                    $transaction_party_req_s = data_get($transfer, 'user.fullName');

                if ($user->special)
                    $transaction_party_req = $transaction_party_req_s;
                else
                    $transaction_party_req = "مشاهده فقط برای سرگروه";


//                $transaction_party = "مشاهده فقط برای سرگروه";
                if (data_get($user, 'customer')) {
                    $transaction_party_s = data_get($user, 'fullName');
                    $transaction_party_s .= "(" . data_get($head, 'fullName') . ")";
                } else
                    $transaction_party_s = data_get($user, 'user.fullName');

                if (data_get($transfer, "user.special"))
                    $transaction_party = $transaction_party_s;
                else
                    $transaction_party = "مشاهده فقط برای سرگروه";

            } elseif ($transfer->user->role == "colleague" && $user->role == "customer") {
//                $transaction_party_req = "مشاهده فقط برای سرگروه";
                if (data_get($transfer, 'user'))
                    $transaction_party_req_s = data_get($transfer, 'user.fullName');

                if ($user->special)
                    $transaction_party_req = $transaction_party_req_s;
                else
                    $transaction_party_req = "مشاهده فقط برای سرگروه";

                if (data_get($user, 'customer')) {
                    $transaction_party = data_get($user, 'fullName');
                    $transaction_party .= "(" . data_get($head, 'fullName') . ")";
                } else
                    $transaction_party = data_get($user, 'fullName');

            } elseif ($transfer->user->role == "customer" && $user->role == "colleague") {
                if (data_get($transfer, 'user.customer')) {
                    $transaction_party_req = data_get($transfer, 'user.fullName');
                    $transaction_party_req .= "(" . data_get($colleague, 'fullName') . ")";
                } else
                    $transaction_party_req = data_get($transfer, 'user.fullName');

                $transaction_party = "مشاهده فقط برای سرگروه";
                $transaction_party_s = data_get($user, 'fullName');

                if (data_get($transfer, "user.special"))
                    $transaction_party = $transaction_party_s;
                else
                    $transaction_party = "مشاهده فقط برای سرگروه";
            } elseif ($transfer->user->role == "colleague" && $user->role == "colleague") {
                $transaction_party_req = data_get($transfer, 'user.fullName');
                $transaction_party = $user->fullName;
            }

            $title = $transfer->message_request_me;
            $number = data_get($order_buy, "number");
            $type = $transfer->type;
            $description = $transfer->description;
            $parties = $transaction_party_req;
            $date = $transfer->date;
            $factor = data_get($order_buy, 'id');
            $user_id = data_get($user, 'telegram_id');
            dispatch(new CancelOrderUser(
                $title,
                $number,
                $type,
                $description,
                $parties,
                $date,
                $factor,
                $user_id,
            ));
            if ($user->role == "customer" && data_get($user, 'customer')) {
                $customer = data_get($user, 'fullName');
                $parties = $transaction_party_req_s;
                $user_id = data_get($user, 'customer.telegram_id');
                dispatch(new CancelOrderCustomer(
                    $title,
                    $number,
                    $type,
                    $description,
                    $parties,
                    $date,
                    $factor,
                    $user_id,
                    $customer
                ));
            }
            $user_id = $transfer->user->telegram_id;
            $title = $transfer->message_request;
            $parties = $transaction_party;
            dispatch(new CancelOrderUser(
                $title,
                $number,
                $type,
                $description,
                $parties,
                $date,
                $factor,
                $user_id,
            ));
            if ($transfer->user->role == "customer" && data_get($transfer, 'user.customer')) {
                $customer = data_get($transfer, 'user.fullName');
                $parties = $transaction_party_s;
                $user_id = data_get($transfer, 'user.customer.telegram_id');
                dispatch(new CancelOrderCustomer(
                    $title,
                    $number,
                    $type,
                    $description,
                    $parties,
                    $date,
                    $factor,
                    $user_id,
                    $customer
                ));
            }
            $order_buy->dailyRequest->delete();
            $order_buy->delete();
        }


    }


}
