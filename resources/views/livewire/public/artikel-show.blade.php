<div>
    <article class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('artikel.index') }}" class="text-sm font-medium text-primary-500 hover:text-primary-700">← Kembali ke Edukasi</a>

        <div class="mt-4">
            <span class="text-xs font-medium uppercase tracking-wide text-primary-500">{{ ucfirst($article->tipe) }}</span>
            <h1 class="mt-1 text-3xl font-semibold leading-tight text-primary-900">{{ $article->judul }}</h1>
            <p class="mt-2 text-sm text-gray-400">
                Oleh {{ $article->author?->name ?? 'Tim Niti Resik' }} · {{ $article->published_at?->translatedFormat('d F Y') }}
            </p>
        </div>

        @if ($article->thumbnail)
            <img src="{{ asset('storage/' . $article->thumbnail) }}" alt="" class="mt-6 aspect-video w-full rounded-xl border border-gray-200 object-cover">
        @endif

        <div class="article-content mt-8">
            {!! $html !!}
        </div>
    </article>
</div>