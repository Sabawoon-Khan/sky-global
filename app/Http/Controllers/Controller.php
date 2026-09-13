<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesMisUsers;

abstract class Controller
{
    use NotifiesMisUsers;
}
