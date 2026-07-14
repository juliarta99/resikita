<div class="p-6 max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kelola Edukasi / Artikel</h1>
            <p class="text-slate-500 text-sm">Artikel, Panduan, Tutorial, dan Jurnal untuk aplikasi masyarakat.</p>
        </div>
        <button wire:click="create"
            class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2.5 rounded-lg font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Artikel
        </button>
    </div>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari judul..."
            class="rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
        <select wire:model.live="filterTipe" class="rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Tipe</option>
            <option value="artikel">Artikel</option>
            <option value="panduan">Panduan</option>
            <option value="tutorial">Tutorial</option>
            <option value="jurnal">Jurnal</option>
        </select>
        <select wire:model.live="filterStatus" class="rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Status</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
        </select>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Artikel</th>
                    <th class="px-4 py-3 font-medium">Tipe</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-center">Unggulan</th>
                    <th class="px-4 py-3 font-medium text-center">Dilihat</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($articles as $a)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center shrink-0">
                                    @if ($a->thumbnail)
                                        <img src="{{ asset('storage/'.$a->thumbnail) }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4-4 4 4 4-6 4 6M4 6h16v12H4z"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-800 line-clamp-1">{{ $a->judul }}</div>
                                    <div class="text-xs text-slate-400">/{{ $a->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php $warna = ['artikel'=>'bg-blue-100 text-blue-700','panduan'=>'bg-emerald-100 text-emerald-700','tutorial'=>'bg-violet-100 text-violet-700','jurnal'=>'bg-indigo-100 text-indigo-700'][$a->tipe] ?? 'bg-slate-100 text-slate-600'; @endphp
                            <span class="px-2 py-1 rounded-md text-xs font-semibold capitalize {{ $warna }}">{{ $a->tipe }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-md text-xs font-semibold {{ $a->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($a->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleUnggulan({{ $a->id }})" title="Tandai unggulan">
                                <svg class="w-5 h-5 mx-auto {{ $a->is_unggulan ? 'text-amber-400 fill-amber-400' : 'text-slate-300' }}" fill="{{ $a->is_unggulan ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5l2.6 5.27 5.82.85-4.21 4.1 1 5.8L11.48 17l-5.2 2.52 1-5.8-4.21-4.1 5.82-.85z"/>
                                </svg>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ number_format($a->dilihat) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $a->published_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $a->id }})" class="p-2 rounded-lg hover:bg-slate-100 text-slate-600" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="delete({{ $a->id }})" wire:confirm="Hapus artikel ini?" class="p-2 rounded-lg hover:bg-red-50 text-red-600" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada artikel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $articles->links() }}</div>

    {{-- ============ MODAL FORM ============ --}}
    <div x-data="{ open: @entangle('showForm') }" x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4">
        <div @click.outside="open = false" class="bg-white rounded-2xl w-full max-w-3xl my-8 shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-800">{{ $articleId ? 'Edit Artikel' : 'Tambah Artikel' }}</h2>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="px-6 py-5 space-y-4">
                {{-- Judul + slug --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.blur="judul"
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                        @error('judul') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Slug <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="slug"
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                        @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Tipe + status + tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipe</label>
                        <select wire:model="tipe"
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="artikel">Artikel</option>
                            <option value="panduan">Panduan</option>
                            <option value="tutorial">Tutorial</option>
                            <option value="jurnal">Jurnal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select wire:model="status"
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Terbit</label>
                        <input type="date" wire:model="published_at"
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                {{-- Video + unggulan --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">URL Video (opsional, mis. YouTube)</label>
                        <input type="url" wire:model="video_url" placeholder="https://youtu.be/..."
                            class="w-full rounded-lg border border-slate-300 py-2 px-4 focus:border-emerald-500 focus:ring-emerald-500">
                        @error('video_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 py-2.5">
                        <input type="checkbox" wire:model="is_unggulan"
                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-700">Tandai sebagai <b>Konten Unggulan</b></span>
                    </label>
                </div>

                {{-- Thumbnail --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Thumbnail</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 rounded-lg border border-slate-200 bg-slate-100 overflow-hidden flex items-center justify-center">
                            @if ($thumbnail)
                                <img src="{{ $thumbnail->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif ($existingThumbnail)
                                <img src="{{ asset('storage/'.$existingThumbnail) }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4-4 4 4 4-6 4 6M4 6h16v12H4z"/></svg>
                            @endif
                        </div>
                        <input type="file" wire:model="thumbnail" accept="image/*"
                            class="text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div wire:loading wire:target="thumbnail" class="text-xs text-slate-400 mt-1">Mengunggah…</div>
                    @error('thumbnail') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Konten (WYSIWYG -> HTML) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Konten <span class="text-red-500">*</span></label>
                    <div wire:ignore x-data="articleEditor(@js($konten))" x-init="init()"
                         class="rounded-lg border border-slate-300 py-2 px-4 overflow-hidden">
                        <div x-ref="editor" style="min-height:240px" class="bg-white"></div>
                    </div>
                    @error('konten') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol <b>video</b> di toolbar untuk menyematkan YouTube, atau tempel tautannya langsung di konten.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="open = false" class="px-4 py-2.5 rounded-lg border border-slate-300 py-2 px-4 text-slate-700 font-medium hover:bg-slate-50">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-semibold">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quill (WYSIWYG) — Quill sudah dimuat di layout --}}
    @push('styles')
        <style>
            .ql-toolbar.ql-snow{border-color:#cbd5e1;border-top-left-radius:.5rem;border-top-right-radius:.5rem;background:#f8fafc}
            .ql-container.ql-snow{border-color:#cbd5e1;border-bottom-left-radius:.5rem;border-bottom-right-radius:.5rem}
            .ql-editor{min-height:240px;font-size:14px}
            .ql-editor iframe{width:100%;aspect-ratio:16/9;border-radius:.5rem}
        </style>
    @endpush
    @push('scripts')
        <script>
            function articleEditor(initial) {
                return {
                    quill: null,
                    init() {
                        this.quill = new Quill(this.$refs.editor, {
                            theme: 'snow',
                            placeholder: 'Tulis konten di sini...',
                            modules: {
                                toolbar: [
                                    [{ header: [2, 3, false] }],
                                    ['bold', 'italic', 'underline', 'strike'],
                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                    ['blockquote', 'code-block'],
                                    ['link', 'video'],
                                    ['clean'],
                                ],
                            },
                        });
                        if (initial) this.quill.root.innerHTML = initial;
                        this.quill.on('text-change', () => {
                            this.$wire.set('konten', this.quill.root.innerHTML, false);
                        });
                        // Isi ulang editor saat create/edit (event dari Livewire)
                        window.Livewire.on('editor-set', (payload) => {
                            const html = (payload && (payload.html ?? (Array.isArray(payload) ? payload[0]?.html : ''))) || '';
                            this.quill.root.innerHTML = html;
                            this.$wire.set('konten', html, false);
                        });
                    },
                };
            }
        </script>
    @endpush
</div>