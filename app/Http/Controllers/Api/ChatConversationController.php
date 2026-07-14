<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    /** Daftar percakapan chatbot milik pengguna. */
    public function index(Request $request)
    {
        $convos = ChatConversation::where('user_id', $request->user()->id)
            ->latest('updated_at')->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'title'        => $c->title,
                'jumlah_pesan' => count($this->messagesArray($c)),
                'cuplikan'     => $this->lastText($c),
                'updated_at'   => $c->updated_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $convos]);
    }

    /** Detail satu percakapan (seluruh pesan). */
    public function show(Request $request, ChatConversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        return response()->json(['data' => [
            'id'         => $conversation->id,
            'title'      => $conversation->title,
            'messages'   => $this->messagesArray($conversation),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ]]);
    }

    /** Ubah judul percakapan. */
    public function update(Request $request, ChatConversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);
        $data = $request->validate(['title' => 'required|string|max:100']);
        $conversation->update(['title' => trim($data['title'])]);

        return response()->json(['data' => [
            'id'    => $conversation->id,
            'title' => $conversation->title,
        ]]);
    }

    public function destroy(Request $request, ChatConversation $conversation)
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);
        $conversation->delete();

        return response()->json(['message' => 'Percakapan dihapus.']);
    }

    /** Selalu kembalikan array pesan, walau kolom terbaca sebagai string JSON. */
    private function messagesArray(ChatConversation $c): array
    {
        $m = $c->messages;
        if (is_string($m)) {
            $m = json_decode($m, true);
        }
        return is_array($m) ? $m : [];
    }

    private function lastText(ChatConversation $c): string
    {
        $m = $this->messagesArray($c);
        if (! $m) {
            return '';
        }
        $last = end($m);
        return is_array($last) ? ($last['text'] ?? '') : '';
    }
}