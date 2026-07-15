<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Integration\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function __invoke(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'pesan'           => 'required|string|max:1000',
            'conversation_id' => 'nullable|integer',
            'riwayat'         => 'nullable|array|max:20',
            'riwayat.*.role'  => 'required_with:riwayat|in:user,model',
            'riwayat.*.text'  => 'required_with:riwayat|string|max:2000',
        ]);

        $user = $request->user();

        $convo = ! empty($data['conversation_id'])
            ? ChatConversation::where('user_id', $user->id)->find($data['conversation_id'])
            : null;

        $rawContext = $convo
            ? collect($convo->messages ?? [])
                ->filter(fn ($m) => is_array($m) && isset($m['role'], $m['text']) && in_array($m['role'], ['user', 'model'], true) && trim((string) $m['text']) !== '')
                ->map(fn ($m) => ['role' => $m['role'], 'text' => (string) $m['text']])
                ->values()->all()
            : ($data['riwayat'] ?? []);

        $context = $this->normalizeHistory($rawContext);
        $context[] = ['role' => 'user', 'text' => $data['pesan']];

        // 1) Coba dengan konteks (ditangkap + dilog bila error).
        $balasan = $this->askGemini($gemini, $context, 'dengan-konteks', [
            'conversation_id' => $convo?->id,
            'pesan'           => $data['pesan'],
            'jumlah_konteks'  => count($context),
        ]);

        // 2) Bila gagal/kosong, retry TANPA riwayat.
        if ($balasan === '') {
            $balasan = $this->askGemini($gemini, [['role' => 'user', 'text' => $data['pesan']]], 'tanpa-konteks', [
                'conversation_id' => $convo?->id,
                'pesan'           => $data['pesan'],
            ]);
        }

        // 3) Fallback terakhir.
        if ($balasan === '') {
            $balasan = 'Maaf, saya belum bisa menjawab itu sekarang. Coba tanyakan dengan cara lain seputar pengelolaan sampah, daur ulang, atau bank sampah, ya.';
        }

        $ms = (int) (now()->timestamp * 1000);
        $userMsg = ['role' => 'user', 'text' => $data['pesan'], 'at' => $ms];
        $botMsg  = ['role' => 'model', 'text' => $balasan, 'at' => $ms + 1000];

        try {
            if (! $convo) {
                $seed = $this->normalizeHistory(
                    collect($data['riwayat'] ?? [])->map(fn ($m) => ['role' => $m['role'], 'text' => $m['text']])->all()
                );
                $seed = array_map(fn ($m) => $m + ['at' => $ms], $seed);

                $convo = ChatConversation::create([
                    'user_id'  => $user->id,
                    'title'    => mb_substr($data['pesan'], 0, 40),
                    'messages' => array_merge($seed, [$userMsg, $botMsg]),
                ]);
            } else {
                $msgs = $convo->messages;
                if (is_string($msgs)) {
                    $msgs = json_decode($msgs, true);
                }
                if (! is_array($msgs)) {
                    $msgs = [];
                }
                $msgs[] = $userMsg;
                $msgs[] = $botMsg;
                $convo->messages = $msgs;
                if (! $convo->title || $convo->title === 'Percakapan Baru') {
                    $convo->title = mb_substr($data['pesan'], 0, 40);
                }
                $convo->save();
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan respons hanya karena penyimpanan; cukup catat.
            Log::error('Chatbot gagal menyimpan percakapan: ' . $e->getMessage(), [
                'exception'       => get_class($e),
                'conversation_id' => $convo?->id,
                'user_id'         => $user->id,
            ]);

            return response()->json(['data' => [
                'balasan'         => $balasan,
                'conversation_id' => $convo?->id,
                'pesan'           => $userMsg,
                'jawaban'         => $botMsg,
            ]]);
        }

        return response()->json(['data' => [
            'balasan'         => $balasan,
            'conversation_id' => $convo->id,
            'pesan'           => $userMsg,
            'jawaban'         => $botMsg,
        ]]);
    }

    /**
     * Panggil Gemini dengan aman: selalu kembalikan string.
     * Bila melempar exception ATAU kosong -> dicatat ke log dan mengembalikan ''.
     */
    private function askGemini(GeminiService $gemini, array $context, string $tag, array $meta = []): string
    {
        try {
            $balasan = trim((string) $gemini->chat($context));

            if ($balasan === '') {
                Log::warning("Chatbot: balasan Gemini kosong [{$tag}]", $meta);
            }

            return $balasan;
        } catch (\Throwable $e) {
            Log::error("Chatbot: Gemini error [{$tag}] " . $e->getMessage(), array_merge($meta, [
                'exception' => get_class($e),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]));

            return '';
        }
    }

    /**
     * Rapikan riwayat agar aman untuk Gemini:
     * - buang pesan kosong
     * - buang 'model' yang muncul sebelum ada 'user'
     * - paksa berselang-seling (peran sama berturut-turut -> ambil yang terakhir)
     * - batasi jumlah turn agar tidak terlalu panjang
     */
    private function normalizeHistory(array $history, int $maxTurns = 12): array
    {
        $clean = [];

        foreach ($history as $m) {
            $role = (($m['role'] ?? '') === 'model') ? 'model' : 'user';
            $text = trim((string) ($m['text'] ?? ''));

            if ($text === '') {
                continue;
            }
            if (empty($clean) && $role === 'model') {
                continue;
            }
            if (! empty($clean) && $clean[count($clean) - 1]['role'] === $role) {
                $clean[count($clean) - 1] = ['role' => $role, 'text' => $text];
                continue;
            }
            $clean[] = ['role' => $role, 'text' => $text];
        }

        if (count($clean) > $maxTurns) {
            $clean = array_slice($clean, -$maxTurns);
            while (! empty($clean) && $clean[0]['role'] === 'model') {
                array_shift($clean);
            }
        }

        return array_values($clean);
    }
}