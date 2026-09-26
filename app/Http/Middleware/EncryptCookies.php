<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * Cookies that are kept in plain text. The colour scheme is written by
     * JavaScript and read back by current_theme() on the server, so it must
     * not be encrypted or the value would be discarded on the next request.
     *
     * @var array
     */
    protected $except = [
        'app_theme',
    ];
}
