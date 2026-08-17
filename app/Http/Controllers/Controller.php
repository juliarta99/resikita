<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Induk seluruh controller.
 *
 * Menyertakan AuthorizesRequests supaya `$this->authorize()` tersedia
 * di setiap controller. Sejak Laravel 11 trait ini tidak lagi ikut
 * secara bawaan, dan tanpanya pemanggilan Policy akan gagal diam-diam
 * saat pengembangan, persis jenis kegagalan yang paling tidak
 * diinginkan pada lapis otorisasi.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
