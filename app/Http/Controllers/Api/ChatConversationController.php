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
                'jumlah_pesan' => count($c->messages ?? []),
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
            'messages'   => $conversation->messages ?? [],
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

    private function lastText(ChatConversation $c): string
    {
        $m = $c->messages ?? [];
        return count($m) ? (end($m)['text'] ?? '') : '';
    }
}