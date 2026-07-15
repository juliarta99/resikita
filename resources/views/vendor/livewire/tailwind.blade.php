@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">

            {{-- ===== Mobile: Sebelumnya / Berikutnya ===== --}}
            <div class="flex flex-1 items-center justify-between sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex cursor-default items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium leading-5 text-gray-300">
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-600 transition duration-150 ease-in-out hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-1 focus:ring-primary-500 active:bg-primary-100">
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                </span>

                {{-- Penanda halaman: di layar sempit pengguna tetap tahu posisinya --}}
                <span class="text-xs text-gray-400">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-600 transition duration-150 ease-in-out hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-1 focus:ring-primary-500 active:bg-primary-100">
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span class="relative inline-flex cursor-default items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium leading-5 text-gray-300">
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </span>
            </div>

            {{-- ===== Desktop: keterangan + deretan nomor ===== --}}
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm leading-5 text-gray-500">
                        <span>{!! __('Showing') !!}</span>
                        <span class="font-semibold text-primary-900">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('to') !!}</span>
                        <span class="font-semibold text-primary-900">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('of') !!}</span>
                        <span class="font-semibold text-primary-900">{{ $paginator->total() }}</span>
                        <span>{!! __('results') !!}</span>
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex rounded-lg shadow-sm rtl:flex-row-reverse">
                        <span>
                            {{-- Previous Page Link --}}
                            @if ($paginator->onFirstPage())
                                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                    <span class="relative inline-flex cursor-default items-center rounded-l-lg border border-gray-200 bg-gray-50 px-2 py-2 text-sm font-medium leading-5 text-gray-300" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </span>
                            @else
                                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center rounded-l-lg border border-gray-200 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-500 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-1 focus:ring-primary-500 active:bg-primary-100" aria-label="{{ __('pagination.previous') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </span>

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative -ml-px inline-flex cursor-default items-center border border-gray-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-400">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            {{-- Halaman aktif: latar primary, teks putih.
                                                 Bawaan Livewire memakai bg-white/text-gray-500 —
                                                 sama persis dengan halaman lain, sehingga posisi
                                                 pengguna tidak terlihat sama sekali. --}}
                                            <span aria-current="page">
                                                <span class="relative -ml-px inline-flex cursor-default items-center border border-primary-500 bg-primary-500 px-4 py-2 text-sm font-semibold leading-5 text-white">{{ $page }}</span>
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative -ml-px inline-flex items-center border border-gray-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-gray-600 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-1 focus:ring-primary-500 active:bg-primary-100" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        <span>
                            {{-- Next Page Link --}}
                            @if ($paginator->hasMorePages())
                                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative -ml-px inline-flex items-center rounded-r-lg border border-gray-200 bg-white px-2 py-2 text-sm font-medium leading-5 text-gray-500 transition duration-150 ease-in-out hover:bg-primary-50 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-1 focus:ring-primary-500 active:bg-primary-100" aria-label="{{ __('pagination.next') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @else
                                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                    <span class="relative -ml-px inline-flex cursor-default items-center rounded-r-lg border border-gray-200 bg-gray-50 px-2 py-2 text-sm font-medium leading-5 text-gray-300" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>