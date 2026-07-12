<?php

namespace App\Livewire\Eksekutif;

use App\Models\BanjarDinas;
use Illuminate\Support\Facades\Auth;

trait ResolvesScope
{
    /** Cakupan wilayah berdasarkan peran pengguna login. */
    protected function scope(): array
    {
        /** @var \App\Models\User $u */
        $u = Auth::user();

        if ($u->hasRole('bupati')) {
            return ['type' => 'kabupaten', 'id' => null, 'label' => 'Kabupaten Badung',
                'banjarIds' => BanjarDinas::pluck('id')];
        }
        if ($u->hasRole('camat')) {
            return ['type' => 'kecamatan', 'id' => $u->kecamatan_id, 'label' => 'Kecamatan ' . ($u->kecamatan?->nama ?? '-'),
                'banjarIds' => BanjarDinas::whereHas('kelurahan', fn ($q) => $q->where('kecamatan_id', $u->kecamatan_id))->pluck('id')];
        }
        if ($u->hasRole('lurah')) {
            return ['type' => 'kelurahan', 'id' => $u->kelurahan_id, 'label' => ($u->kelurahan?->nama ?? '-'),
                'banjarIds' => BanjarDinas::where('kelurahan_id', $u->kelurahan_id)->pluck('id')];
        }

        return ['type' => 'banjar', 'id' => $u->banjar_id, 'label' => ($u->banjarDinas?->nama ?? '-'),
            'banjarIds' => collect([$u->banjar_id])];
    }
}