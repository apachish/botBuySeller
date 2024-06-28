<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;

class UglyWord extends Model
{
    protected $connection = "mongodb";
    protected $collection = 'ugly_word_collection';
}
