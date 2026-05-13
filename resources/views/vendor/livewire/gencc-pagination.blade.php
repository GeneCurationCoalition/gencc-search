@if ($paginator->hasPages())
    <nav class="mt-10" role="navigation" aria-label="Pagination Navigation">
        {{-- Results summary --}}
        <div class="text-center text-gray-600 mb-4">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </div>

        <ul class="flex justify-center flex-wrap text-sm">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li aria-label="@lang('pagination.previous')">
                    <span class="px-4 py-3 text-gray-400 block border border-r-0 border-gray-300 rounded-l bg-gray-100" aria-hidden="true">&laquo; Previous</span>
                </li>
            @else
                <li>
                    <button
                       type="button"
                       wire:click="previousPage('{{ $paginator->getPageName() }}')"
                       wire:loading.attr="disabled"
                       class="px-4 py-3 block text-blue-900 border border-r-0 border-gray-300 rounded-l hover:text-white hover:bg-blue-900 focus:outline-none focus:shadow-outline"
                       aria-label="@lang('pagination.previous')"
                    >
                        &laquo; Previous
                    </button>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li aria-disabled="true">
                        <span class="px-4 py-3 block text-gray-500 border border-r-0 border-gray-300">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif>
                            @if ($page == $paginator->currentPage())
                                <span class="px-4 py-3 block text-white bg-blue-900 border border-r-0 border-gray-300">{{ $page }}</span>
                            @else
                                <button
                                   type="button"
                                   wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                   wire:loading.attr="disabled"
                                   class="px-4 py-3 block text-blue-900 border border-r-0 border-gray-300 hover:text-white hover:bg-blue-900 focus:outline-none focus:shadow-outline"
                                   aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <button
                       type="button"
                       wire:click="nextPage('{{ $paginator->getPageName() }}')"
                       wire:loading.attr="disabled"
                       class="px-4 py-3 block text-blue-900 border border-gray-300 rounded-r hover:text-white hover:bg-blue-900 focus:outline-none focus:shadow-outline"
                       aria-label="@lang('pagination.next')"
                    >
                        Next &raquo;
                    </button>
                </li>
            @else
                <li aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="px-4 py-3 block text-gray-400 border border-gray-300 rounded-r bg-gray-100" aria-hidden="true">Next &raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
