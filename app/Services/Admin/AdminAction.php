<?php

namespace App\Services\Admin;


use App\Services\TextServices;

 class AdminAction extends TextServices
{
    public function __construct($token)
    {
        parent::__construct($token);
    }

}
