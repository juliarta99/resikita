<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Keluar dari sesi web.
 *
 * Controller biasa, bukan komponen Livewire: keluar harus tetap bekerja
 * ketika JavaScript gagal dimuat. Formulir POST dengan token CSRF adalah
 * bentuk paling sederhana yang tidak bisa dipicu situs lain lewat
 * tautan gambar atau iframe.
 */
class KeluarController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }
}
