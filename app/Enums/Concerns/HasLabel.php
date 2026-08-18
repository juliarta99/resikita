<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Setiap enum status di Resikita wajib bisa menyebut namanya sendiri
 * dalam Bahasa Indonesia.
 *
 * Alasannya disiplin arsitektur: kalau label hidup di Blade atau di
 * komponen Livewire, web dan mobile akan menyebut status yang sama
 * dengan kata yang berbeda. Label ikut enum, jadi satu sumber kebenaran.
 */
interface HasLabel
{
    public function label(): string;
}
