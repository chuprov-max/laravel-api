<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    const STATUS_CREATED = 1;
    const STATUS_PREPARED_FOR_REQUEST_TO_YANDEX = 2;
    const STATUS_SEND_REQUEST_TO_YANDEX = 3;
}
