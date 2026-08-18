<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\SumberInput;
use App\Livewire\Concerns\MemberiUmpanBalik;
use App\Models\ChatSesi;
use App\Services\Chatbot\ChatbotService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Asisten literasi lingkungan di panel web.
 *
 * Komponen yang sama dipakai bank sampah dan UMKM, keduanya punya
 * permission `chatbot.pakai`. Tidak ada percabangan per role di sini:
 * konteks wilayah dan seluruh aturan jawaban ditentukan ChatbotService
 * dan GeminiService, sehingga jawaban di web identik dengan yang
 * diterima aplikasi ponsel.
 *
 * Masukan suara ditangani peramban lewat Web Speech API dan hasilnya
 * masuk sebagai teks biasa dengan `sumber_input = suara`. Peladen tidak
 * pernah menerima berkas audio: menyimpan rekaman suara warga
 * menciptakan kewajiban perlindungan data yang tidak sepadan dengan
 * manfaatnya, sementara yang dibutuhkan sistem hanya teksnya.
 */
#[Title('Asisten Lingkungan')]
class Asisten extends Component
{
    use MemberiUmpanBalik;

    #[Url(as: 'sesi', except: null)]
    public ?int $sesiId = null;

    public string $pesan = '';

    /** Menandai pertanyaan yang didiktekan, bukan diketik. */
    public bool $lewatSuara = false;

    public function pilihSesi(?int $id): void
    {
        $this->sesiId = $id;
        $this->reset(['pesan', 'lewatSuara']);
    }

    public function sesiBaru(ChatbotService $chatbot): void
    {
        $sesi = $chatbot->buatSesi(auth()->user());

        $this->pilihSesi($sesi->id);
    }

    public function kirim(ChatbotService $chatbot): void
    {
        $this->validate(
            ['pesan' => ['required', 'string', 'min:2', 'max:2000']],
            ['pesan.required' => 'Tulis atau diktekan pertanyaan Anda lebih dulu.'],
        );

        $sesi = $this->sesiSaatIni();

        $hasil = $this->jalankan(fn (): array => $chatbot->kirimPesan(
            auth()->user(),
            $this->pesan,
            $sesi,
            $this->lewatSuara ? SumberInput::Suara : SumberInput::Ketik,
        ));

        if ($hasil !== null) {
            $this->sesiId = $hasil['sesi']->id;
        }

        $this->reset(['pesan', 'lewatSuara']);
    }

    public function hapusSesi(int $id, ChatbotService $chatbot): void
    {
        $sesi = ChatSesi::findOrFail($id);

        $this->authorize('delete', $sesi);

        $chatbot->hapusSesi($sesi);

        if ($this->sesiId === $id) {
            $this->sesiId = null;
        }

        $this->pesanSukses('Percakapan dihapus.');
    }

    /**
     * Sesi yang sedang dibuka, kalau memang milik pengguna ini.
     *
     * Pemeriksaan kepemilikan dilakukan di sini, bukan dipercayakan
     * kepada id di URL. Riwayat percakapan sepenuhnya milik penanyanya,
     * tidak ada pengecualian, termasuk untuk admin.
     */
    private function sesiSaatIni(): ?ChatSesi
    {
        if ($this->sesiId === null) {
            return null;
        }

        $sesi = ChatSesi::query()
            ->where('user_id', auth()->id())
            ->find($this->sesiId);

        if ($sesi === null) {
            $this->sesiId = null;
        }

        return $sesi;
    }

    public function render(ChatbotService $chatbot)
    {
        $sesi = $this->sesiSaatIni();

        return view('livewire.asisten', [
            'sesi' => $sesi !== null ? $chatbot->riwayatPesan($sesi) : null,
            'daftarSesi' => $chatbot->daftarSesi(auth()->user(), 15),
            'saran' => $chatbot->saranPertanyaan(auth()->user()),
        ]);
    }
}
