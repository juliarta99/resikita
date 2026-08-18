<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\SumberInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotRequest;
use App\Http\Resources\ChatPesanResource;
use App\Http\Resources\ChatSesiResource;
use App\Models\ChatPesan;
use App\Models\ChatSesi;
use App\Services\Chatbot\ChatbotService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asisten literasi lingkungan.
 *
 * Controller ini tidak menyusun satu pun kalimat instruksi. Peran,
 * ruang lingkup, batasan, dan konteks wilayah seluruhnya ditentukan
 * ChatbotService dan GeminiService, sehingga jawaban yang diterima
 * aplikasi mobile dan jawaban yang muncul di web berasal dari aturan
 * yang sama persis.
 */
class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotService $chatbot,
    ) {}

    public function sesi(Request $request): JsonResponse
    {
        return ApiResponse::halaman(
            ChatSesiResource::collection(
                $this->chatbot->daftarSesi($request->user(), $request->integer('per_page', 20)),
            ),
        );
    }

    public function buatSesi(Request $request): JsonResponse
    {
        $sesi = $this->chatbot->buatSesi(
            $request->user(),
            $request->string('judul')->toString() ?: null,
        );

        return ApiResponse::resource(
            new ChatSesiResource($sesi->load('wilayahKonteks')),
            'Sesi percakapan dibuat.',
            201,
        );
    }

    public function detailSesi(ChatSesi $sesi): JsonResponse
    {
        $this->authorize('view', $sesi);

        return ApiResponse::resource(
            new ChatSesiResource($this->chatbot->riwayatPesan($sesi)),
        );
    }

    public function hapusSesi(ChatSesi $sesi): JsonResponse
    {
        $this->authorize('delete', $sesi);

        $this->chatbot->hapusSesi($sesi);

        return ApiResponse::kosong('Sesi percakapan dihapus.');
    }

    /**
     * Kirim satu pertanyaan.
     *
     * `sesi_id` boleh kosong. Percakapan pertama tidak perlu dua
     * pemanggilan, layar chat yang baru dibuka bisa langsung mengirim
     * pertanyaan, dan sesinya dibuat di peladen.
     */
    public function kirim(ChatbotRequest $request): JsonResponse
    {
        $sesi = null;

        if ($request->filled('sesi_id')) {
            $sesi = ChatSesi::findOrFail($request->integer('sesi_id'));
            $this->authorize('update', $sesi);
        }

        $hasil = $this->chatbot->kirimPesan(
            $request->user(),
            $request->string('pesan')->toString(),
            $sesi,
            SumberInput::tryFrom((string) $request->input('sumber_input')),
        );

        return ApiResponse::sukses([
            'sesi_id' => $hasil['sesi']->id,
            'judul_sesi' => $hasil['sesi']->judul,
            'pesan' => (new ChatPesanResource($hasil['pesan']))->resolve(),
        ]);
    }

    /**
     * Tandai balasan sudah dibacakan pembaca suara.
     *
     * Angkanya bukan hiasan: kolom ini yang memberi bukti nyata seberapa
     * banyak orang benar-benar memakai jalur suara, alih-alih klaim
     * inklusivitas tanpa data.
     */
    public function tandaiDibacakan(ChatPesan $pesan): JsonResponse
    {
        $this->authorize('update', $pesan->sesi);

        return ApiResponse::resource(
            new ChatPesanResource($this->chatbot->tandaiDibacakan($pesan)),
        );
    }

    public function saranPertanyaan(Request $request): JsonResponse
    {
        return ApiResponse::sukses([
            'saran' => $this->chatbot->saranPertanyaan($request->user()),
        ]);
    }
}
