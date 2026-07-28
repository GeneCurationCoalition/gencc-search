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

        <div class="col-span-12 xl:col-span-5 mt-3">
            <div class="relative inline-block text-left w-full " x-data="{ open: false }">
                <div @click="open = true">
                    <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" aria-haspopup="true" aria-expanded="true">
                        Dissenting submitters
                        <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                        @if(count($hidden_dissenters))
                            {{ count($dissenter_options) - count($hidden_dissenters) }} of
                        @endif
                        {{ count($dissenter_options) }}</span>
                        <i class="fas fa-angle-down ml-1"></i>
                    </button>
                </div>
                <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        @foreach ($dissenter_options as $option)
                            <button type="button" wire:click="toggleDissenter('{{ $option['slug'] }}')"
                                    class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 {{ $option['count'] === 0 ? 'text-gray-400' : 'text-gray-700' }} hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                                    @if(in_array($option['slug'], $hidden_dissenters))
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
    </div>

    <div class="mb-3">
        @foreach ($tier_labels as $tier => $label)
            @if(in_array($tier, $hidden_tiers))
                <button type="button" wire:click="toggleTier('{{ $tier }}')"
                        class="mb-1 mr-1 inline-block border rounded-full px-3 py-1 text-sm leading-tight border-gray-300 bg-gray-200 text-gray-500 hover:bg-gray-300">
                    <i class="far fa-circle"></i>
                    {{ $label }} ({{ number_format($tier_counts[$tier]) }})
                </button>
            @else
                <button type="button" wire:click="toggleTier('{{ $tier }}')"
                        class="mb-1 mr-1 inline-block border rounded-full px-3 py-1 text-sm leading-tight border-blue-800 bg-white text-blue-800 font-bold hover:bg-gray-100">
                    <i class="fas fa-check-circle"></i>
                    {{ $label }} ({{ number_format($tier_counts[$tier]) }})
                </button>
            @endif
        @endforeach
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
            <div class="col-span-1">
                <button type="button" class="hover:underline" wire:click="sortBy('moi')">MOI {!! $sortCaret('moi') !!}</button>
            </div>
            <div class="col-span-2">
                <span>Strong Evidence</span>
            </div>
            <div class="col-span-2">
                <span>Other Evidence</span>
            </div>
            <div class="col-span-1">
                <span>Range</span>
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
                <div class="col-span-1 pr-2 text-gray-600">{{ $item['moi'] }}</div>

                <div class="col-span-2 pr-2">
                    @foreach ($item['strong'] as $submitter => $classifications)
                        <div class="mb-1">
                            <div class="list-text-desc">{{ $submitter }}</div>
                            @foreach ($classifications as $classification)
                                <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap gencc-{{ Str::slug($classification, '') }} ">{{ $classification }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="col-span-2 pr-2">
                    @foreach ($item['other'] as $submitter => $classifications)
                        <div class="mb-1">
                            <div class="list-text-desc">{{ $submitter }}</div>
                            @foreach ($classifications as $classification)
                                <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap gencc-{{ Str::slug($classification, '') }} ">{{ $classification }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="col-span-1 pr-2">
                    <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap gencc-{{ Str::slug($item['strongest'], '') }} ">{{ $item['strongest'] }}</span>
                    <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white whitespace-no-wrap gencc-{{ Str::slug($item['weakest'], '') }} ">{{ $item['weakest'] }}</span>
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
