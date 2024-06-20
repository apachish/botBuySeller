<?php

namespace App\Services\Admin;



 use App\Services\TextServices;

 class AdminAction extends TextServices
{
     private $transaction_services;

    public function __construct($token)
    {
        $this->transaction_services = new TransactionServices($token);

    }

     public function actionData(){

     }

     public function actionText(){
        switch ($this->getMessage()){
            case "\xF0\x9F\x93\x88معامله":
                $this->transaction_services->menu($this->transaction_services->keyword,$this->getUser()->status,$this->getUser());
                break;
            case "\xF0\x9F\x9A\xAB\xF0\x9F\x9A\xBBممنوع معامله":
                $this->transaction_services->setForbidden();
                break;
        }
     }

     public function actionTextCache(){

     }

}
