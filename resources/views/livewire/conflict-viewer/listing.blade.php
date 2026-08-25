@php
    $sortCaret = function ($field) use ($sortField, $sortDirection) {
        if ($sortField !== $field) {
            return '<i class="fas fa-sort text-gray-400"></i>';
        }

        return $sortDirection === 'asc'
            ? '<i class="fas fa-caret-up"></i>'
            : '<i class="fas fa-caret-down"></i>';
    };

    $filter_enabled = count($active_filters) > 0;
@endphp
<div class="">
    @if($invalidUrlFiltersIgnored)
        <div role="alert" class="flex items-center justify-between bg-yellow-100 border border-yellow-400 text-yellow-900 rounded px-4 py-2 mb-3 text-sm">
            <span>Invalid URL filters were ignored.</span>
            <a href="{{ route('conflict-viewer') }}" class="font-semibold hover:underline whitespace-no-wrap">
                Reset filters
            </a>
        </div>
    @endif

    <div class=" text-xl text-gray-600 mb-2">
        <span class=" font-bold ">{{ number_format($conflicts->total()) }}</span>
        @if($filter_enabled)
            of {{ number_format($total_unfiltered) }}
        @endif
        conflicting gene&ndash;disease&ndash;inheritance assertions
    </div>

    <div class="grid grid-cols-12 gap-1 mb-3">
        <div class="col-span-6 xl:col-span-3 mt-3">
            <input class="input input-text" wire:model.debounce.500ms="gene" type="text" placeholder="Filter by gene symbol...">
        </div>
        <div class="col-span-6 xl:col-span-4 mt-3">
            <input class="input input-text" wire:model.debounce.500ms="disease" type="text" placeholder="Filter by disease name...">
        </div>

        <div class="col-span-8 xl:col-span-3 mt-3">
            <div class="relative inline-block text-left w-full " x-data="{ open: false }">
                <div @click="open = true">
                    <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" aria-haspopup="true" aria-expanded="true">
                        Submitters
                        <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                        @if(count($hidden_submitters))
                            {{ count($submitter_options) - count($hidden_submitters) }} of
                        @endif
                        {{ count($submitter_options) }}</span>
                        <i class="fas fa-angle-down ml-1"></i>
                    </button>
                </div>
                <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        @foreach ($submitter_options as $option)
                            <button type="button" wire:click="toggleSubmitter('{{ $option['slug'] }}')"
                                    class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 {{ $option['count'] === 0 ? 'text-gray-400' : 'text-gray-700' }} hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                                    @if(in_array($option['slug'], $hidden_submitters))
                                        <i class="far fa-circle"></i>
                                    @else
                                        <i class="fas fa-check-circle"></i>
                                    @endif
                                    {{ $option['name'] }} ({{ number_format($option['count']) }})</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-4 xl:col-span-2 mt-3">
            <div class="relative inline-block text-left w-full" x-data="{ open: false }">
                <button type="button"
                        @click="open = ! open"
                        :aria-expanded="open.toString()"
                        class="text-left inline-flex justify-between items-center w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150"
                        aria-haspopup="true">
                    <span>Download</span>
                    <i class="fas fa-angle-down ml-1"></i>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     class="z-20 origin-top-right absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100"
                     style="display: none;">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        <a href="{{ route('conflict-viewer-download', array_merge(['format' => 'csv'], $download_query)) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                           role="menuitem">CSV</a>
                        <a href="{{ route('conflict-viewer-download', array_merge(['format' => 'tsv'], $download_query)) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                           role="menuitem">TSV</a>
                        <a href="{{ route('conflict-viewer-download', array_merge(['format' => 'xlsx'], $download_query)) }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                           role="menuitem">Excel (.xlsx)</a>
                    </div>
                    <div class="px-4 py-3 text-xs leading-4 text-gray-600">
                        Downloads include only data from submitters that permit downloads.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($filter_enabled)
        <div class="mb-3 text-sm text-gray-600">
            Filtering: {{ implode(' · ', $active_filters) }}
            <button type="button" class="ml-2 hover:underline text-blue-800" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    <div class="relative row bg-white">
        <div wire:loading class="w-full h-full absolute block top-0 left-0 bg-white opacity-75 z-10">
                <div class="text-center">
                    <i class="fas fa-circle-notch fa-spin fa-10x text-green-600"></i>
                    <div>Loading...</div>
                </div>
        </div>

        <div class="grid grid-cols-12 gap-1 border-b-2 border-gray-300 pb-1 text-sm text-gray-600 font-bold">
            <div class="col-span-2 pl-3">
                <button type="button" class="hover:underline" wire:click="sortBy('gene_symbol')">Gene {!! $sortCaret('gene_symbol') !!}</button>
            </div>
            <div class="col-span-3">
                <button type="button" class="hover:underline" wire:click="sortBy('disease_name')">Disease {!! $sortCaret('disease_name') !!}</button>
            </div>
            <div class="col-span-2">
                <button type="button" class="hover:underline" wire:click="sortBy('moi')">MOI {!! $sortCaret('moi') !!}</button>
            </div>
            <div class="col-span-2">
                <span>D/S/M <i class="far fa-question-circle text-gray-400" title="D/S/M: Definitive, Strong, Moderate" data-toggle="tooltip" data-placement="top"></i></span>
            </div>
            <div class="col-span-2">
                <span>L/P/R/N <i class="far fa-question-circle text-gray-400" title="L/P/R/N: Limited, Disputed, Refuted, No Known Disease Relationship (P denotes Disputed)" data-toggle="tooltip" data-placement="top"></i></span>
            </div>
            <div class="col-span-1 pr-3 text-right">
                <button type="button" class="hover:underline" wire:click="sortBy('total_count')">Total {!! $sortCaret('total_count') !!}</button>
            </div>
        </div>

    @forelse ($conflicts as $item)
        <div class="row-stripe row-detail border-t-4 border-t-gray-200 border-t-solid py-4">
            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-2 pl-3">
                    <a href="{{ route('gene-show', $item['hgnc_id']) }}" class="list-text-label list-link">
                        {{ $item['gene_symbol'] }}
                        <div class="list-text-desc">{{ $item['hgnc_id'] }}</div>
                    </a>
                </div>
                <div class="col-span-3 pr-2">
                    <a href="{{ route('disease-show', $item['disease_curie']) }}" class="list-link">
                        {{ $item['disease_name'] }}
                        <div class="list-text-desc">{{ $item['disease_curie'] }}</div>
                    </a>
                </div>
                <div class="col-span-2 pr-2 text-gray-600">{{ $item['moi'] }}</div>

                <div class="col-span-2 pr-2">
                    @foreach ($item['strong'] as $submitter => $classifications)
                        <div class="mb-1">
                            <div class="list-text-desc">{{ $submitter }}</div>
                            @foreach ($classifications as $classification)
                                <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap {{ $classification['css_class'] }} ">{{ $classification['label'] }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="col-span-2 pr-2">
                    @foreach ($item['other'] as $submitter => $classifications)
                        <div class="mb-1">
                            <div class="list-text-desc">{{ $submitter }}</div>
                            @foreach ($classifications as $classification)
                                <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap {{ $classification['css_class'] }} ">{{ $classification['label'] }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="col-span-1 pr-3 text-right">
                    <span class="list-text-label">{{ $item['total_count'] }}</span>
                    <div class="list-text-desc">{{ $item['strong_count'] }} / {{ $item['other_count'] }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="border-t border-t-gray-200 border-t-solid pt-2 mt-2">
            <div class="alert alert-info">

            Sorry, we couldn't find anything...
            </div>
        </div>
    @endforelse
    </div>

    {{ $conflicts->links('vendor.livewire.gencc-pagination') }}
</div>
