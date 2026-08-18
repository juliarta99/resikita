<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Enums\PeranChat;
use App\Enums\SumberInput;
use App\Exceptions\AturanBisnisException;
use App\Models\ChatPesan;
use App\Models\ChatSesi;
use App\Models\User;
use App\Services\Integration\GeminiService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Asisten literasi lingkungan, dipakai web dan mobile.
 *
 * ## Riwayat tinggal di satu tempat
 *
 * Skema lama menyimpan percakapan dua kali: kolom longtext
 * `chat_conversations.messages` dan tabel `chat_messages`. Dua salinan
 * yang ditulis di jalur berbeda pasti menyimpang, dan tidak ada cara
 * memutuskan mana yang benar setelahnya. Sekarang hanya tabel
 * `chat_pesan` (CLAUDE.md 4.1).
 *
 * ## Konteks lokal disisipkan, bukan ditanam
 *
 * Wilayah pengguna disimpan pada sesi saat sesi dibuat, lalu diserahkan
 * ke GeminiService setiap giliran. Tidak ada nama daerah di instruksi
 * dasar. Menanam satu daerah di prompt berarti seluruh pengguna di luar
 * daerah itu menerima saran yang tidak berlaku di tempatnya
 * (CLAUDE.md 10.2).
 */
class ChatbotService
{
    /**
     * Jumlah giliran terakhir yang dikirim ulang sebagai konteks.
     *
     * Percakapan panjang tidak dikirim utuh: biayanya naik tiap giliran
     * dan bagian awal percakapan jarang masih relevan. Enam belas pesan
     * kira-kira delapan tanya jawab, cukup untuk menjaga rujukan "itu"
     * dan "tadi" tetap bermakna.
     */
    private const BATAS_KONTEKS = 16;

    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    // ----------------------------------------------------------------
    // Sesi
    // ----------------------------------------------------------------

    public function daftarSesi(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return ChatSesi::query()
            ->where('user_id', $user->id)
            ->with('wilayahKonteks:id,nama,tingkat')
            ->withCount('pesan')
            ->orderByDesc('terakhir_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function buatSesi(User $user, ?string $judul = null): ChatSesi
    {
        return ChatSesi::create([
            'user_id' => $user->id,
            'judul' => $judul !== null && trim($judul) !== ''
                ? mb_substr(trim($judul), 0, 150)
                : 'Percakapan Baru',
            // Wilayah dikunci pada saat sesi dibuat. Kalau pengguna
            // pindah domisili di tengah percakapan, jawaban lama tetap
            // bisa dijelaskan dengan konteks yang dipakai saat itu.
            'wilayah_konteks_id' => $user->wilayah_id,
            'terakhir_at' => now(),
        ]);
    }

    public function riwayatPesan(ChatSesi $sesi): ChatSesi
    {
        return $sesi->load(['pesan', 'wilayahKonteks:id,nama,tingkat']);
    }

    public function hapusSesi(ChatSesi $sesi): void
    {
        $sesi->delete();
    }

    // ----------------------------------------------------------------
    // Percakapan
    // ----------------------------------------------------------------

    /**
     * Kirim satu pertanyaan dan kembalikan balasan asisten.
     *
     * Sesi dibuat otomatis bila belum ada, sehingga aplikasi mobile
     * tidak perlu memanggil dua endpoint untuk memulai percakapan.
     *
     * @return array{sesi: ChatSesi, pesan: ChatPesan}
     */
    public function kirimPesan(
        User $user,
        string $pertanyaan,
        ?ChatSesi $sesi = null,
        ?SumberInput $sumber = null,
    ): array {
        $sesi ??= $this->buatSesi($user);

        if ($sesi->user_id !== $user->id) {
            throw AturanBisnisException::tidakBerwenang('Sesi percakapan ini bukan milik Anda.');
        }

        $pertanyaan = trim($pertanyaan);

        $pesanPengguna = ChatPesan::create([
            'sesi_id' => $sesi->id,
            'role' => PeranChat::User,
            'konten' => $pertanyaan,
            'sumber_input' => $sumber ?? SumberInput::Ketik,
        ]);

        /*
         * Pesan pengguna sengaja disimpan sebelum model dipanggil.
         * Panggilan bisa gagal atau habis waktu; kalau pertanyaannya
         * ikut hilang, pengguna kehilangan kalimat yang baru saja
         * susah payah ia diktekan lewat suara.
         */
        try {
            $jawaban = $this->gemini->jawabChat(
                $this->konteksPercakapan($sesi),
                $this->namaWilayah($sesi),
            );
        } catch (AturanBisnisException $e) {
            $pesanPengguna->delete();

            throw $e;
        }

        return DB::transaction(function () use ($sesi, $pertanyaan, $jawaban): array {
            $balasan = ChatPesan::create([
                'sesi_id' => $sesi->id,
                'role' => PeranChat::Model,
                'konten' => $jawaban,
                'model_version' => $this->gemini->modelVersion(),
            ]);

            $sesi->forceFill([
                'judul' => $sesi->judul === 'Percakapan Baru'
                    ? $this->judulDari($pertanyaan)
                    : $sesi->judul,
                'terakhir_at' => now(),
            ])->save();

            return ['sesi' => $sesi, 'pesan' => $balasan];
        });
    }

    /** Tandai sebuah balasan sudah dibacakan lewat pembaca suara. */
    public function tandaiDibacakan(ChatPesan $pesan): ChatPesan
    {
        if ($pesan->role !== PeranChat::Model) {
            throw AturanBisnisException::karena('Hanya balasan asisten yang bisa ditandai sudah dibacakan.');
        }

        $pesan->forceFill(['dibacakan' => true])->save();

        return $pesan;
    }

    /**
     * Pemantik percakapan untuk layar kosong.
     *
     * Daftarnya tetap dan tidak dibuat AI: pemantik yang berubah-ubah
     * membuat pengguna baru tidak pernah melihat contoh yang sama dua
     * kali, dan memanggil model hanya untuk mengisi layar kosong adalah
     * biaya tanpa manfaat.
     *
     * @return array<int, string>
     */
    public function saranPertanyaan(User $user): array
    {
        $saran = [
            'Bagaimana cara memilah sampah rumah tangga sejak dari dapur?',
            'Baterai dan lampu bekas harus dibuang ke mana?',
            'Apa saja yang bisa dikompos dan apa yang tidak?',
            'Bagaimana cara mulai menabung di bank sampah?',
            'Apa bedanya TPS dan TPS3R?',
            'Bagaimana mengurangi plastik sekali pakai di rumah?',
        ];

        if ($user->wilayah_id !== null && $user->relationLoaded('wilayah') === false) {
            $user->loadMissing('wilayah');
        }

        $wilayah = $user->wilayah?->namaLengkap();

        if ($wilayah !== null) {
            array_unshift($saran, "Ke mana saya bisa menyetor sampah anorganik di {$wilayah}?");
        }

        return $saran;
    }

    // ----------------------------------------------------------------
    // Pembantu
    // ----------------------------------------------------------------

    /**
     * Riwayat yang dikirim ke model, sudah dipangkas dan dirapikan.
     *
     * Gemini menolak percakapan yang tidak berselang-seling peran, dan
     * pemangkasan bisa saja memotong tepat di balasan model. Karena itu
     * potongan yang diawali balasan model dibuang satu pesan lagi
     * sampai diawali pertanyaan pengguna.
     *
     * @return array<int, array{role: PeranChat, konten: string}>
     */
    private function konteksPercakapan(ChatSesi $sesi): array
    {
        $pesan = ChatPesan::query()
            ->where('sesi_id', $sesi->id)
            ->orderByDesc('id')
            ->limit(self::BATAS_KONTEKS)
            ->get()
            ->sortBy('id')
            ->values();

        while ($pesan->isNotEmpty() && $pesan->first()->role !== PeranChat::User) {
            $pesan->shift();
        }

        return $pesan
            ->map(fn (ChatPesan $p): array => ['role' => $p->role, 'konten' => $p->konten])
            ->all();
    }

    private function namaWilayah(ChatSesi $sesi): ?string
    {
        $sesi->loadMissing('wilayahKonteks');

        return $sesi->wilayahKonteks?->namaLengkap();
    }

    /** Judul sesi dari pertanyaan pertama, dipotong pada batas kata. */
    private function judulDari(string $pertanyaan): string
    {
        return Str::limit(Str::squish($pertanyaan), 60, preserveWords: true);
    }
}
