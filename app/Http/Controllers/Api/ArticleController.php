<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /** Daftar konten edukasi. ?tipe=artikel|panduan|tutorial|jurnal &q= &unggulan=1 &sort=populer */
    public function index(Request $request)
    {
        $base = Article::with('author')->where('status', 'published');

        if ($tipe = $request->query('tipe')) {
            $base->where('tipe', $tipe);
        }
        if ($s = $request->query('q')) {
            $base->where('judul', 'like', "%{$s}%");
        }

        $sort = $request->query('sort');

        // Mode unggulan dengan fallback: unggulan -> populer -> terbaru
        if ($request->boolean('unggulan')) {
            $q = (clone $base)->where('is_unggulan', true)->orderByDesc('dilihat');

            // Kalau belum ada artikel unggulan, pakai yang terpopuler
            if (! $q->exists()) {
                $q = (clone $base)->orderByDesc('dilihat')->latest('published_at');
            }
        } else {
            $q = $sort === 'populer'
                ? $base->orderByDesc('dilihat')
                : $base->latest('published_at');
        }

        $data = $q->paginate(10)->through(fn ($a) => [
            'id'           => $a->id,
            'judul'        => $a->judul,
            'slug'         => $a->slug,
            'tipe'         => $a->tipe,
            'thumbnail'    => $a->thumbnail ? asset('storage/' . $a->thumbnail) : null,
            'ringkas'      => Str::limit(trim(strip_tags($a->konten)), 160),
            'penulis'      => $a->author?->name,
            'waktu_baca'   => $this->waktuBaca($a->konten),
            'dilihat'      => (int) $a->dilihat,
            'is_unggulan'  => (bool) $a->is_unggulan,
            'published_at' => $a->published_at?->toIso8601String(),
        ]);

        return response()->json($data);
    }

    public function show(string $slug)
    {
        $a = Article::with('author')->where('slug', $slug)->where('status', 'published')->firstOrFail();
        $a->increment('dilihat');

        return response()->json(['data' => [
            'id'           => $a->id,
            'judul'        => $a->judul,
            'slug'         => $a->slug,
            'tipe'         => $a->tipe,
            'thumbnail'    => $a->thumbnail ? asset('storage/' . $a->thumbnail) : null,
            'konten_html'  => $a->konten,            // HTML dari editor admin
            'video_url'    => $a->video_url,
            'video_embed'  => $this->ytEmbed($a->video_url),
            'penulis'      => $a->author?->name,
            'waktu_baca'   => $this->waktuBaca($a->konten),
            'dilihat'      => (int) $a->dilihat,
            'published_at' => $a->published_at?->toIso8601String(),
        ]]);
    }

    /** Estimasi waktu baca (menit) ~200 kata/menit. */
    private function waktuBaca(?string $html): int
    {
        $kata = str_word_count(trim(strip_tags((string) $html)));
        return max(1, (int) ceil($kata / 200));
    }

    /** Ubah URL YouTube menjadi URL embed; null bila kosong. */
    private function ytEmbed(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        return $url;
    }
}