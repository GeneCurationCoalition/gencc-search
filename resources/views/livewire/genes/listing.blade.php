<div class="">


    <div class=" text-xl text-gray-600 mb-2"><span class=" font-bold ">{{ $genes->total()  }}</span> {{ $tableHeading }}</div>
    <div class="grid grid-cols-12 gap-1 mb-3">
        <div class="col-span-4 xl:col-span-2 mt-3">
            <input class="input input-text" wire:model.debounce.500ms="title" type="text" value="{{ $title }}" placeholder="Filter by gene symbol...">
        </div>
        <div class="col-span-4 xl:col-span-3 mt-3">
            <input class="input input-text" wire:model.debounce.500ms="hasDisease" type="text" value="{{ $hasDisease }}" placeholder="Filter by submitted disease...">
        </div>
        <div class="col-span-4 xl:col-span-2 mt-3">
            <!--
            Tailwind UI components require Tailwind CSS v1.8 and the @tailwindcss/ui plugin.
            Read the documentation to get started: https://tailwindui.com/documentation
            -->
            <div class="relative block text-left" x-data="{ open: false }">
            <div  @click="open = true">
                <span class="rounded-md shadow-sm">
                <button type="button" class=" text-left inline-flex w-full rounded-md border border-gray-300 px-4 py-2 bg-white  leading-5 input-text text-gray-500 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                    @if(count($curations_from_submitters) != count($submitters))
                    Showing {{ count($curations_from_submitters) }}...
                    @else
                    Filter by Submitter
                    @endif <i class="fas fa-caret-down"></i>
                </button>
                </span>
            </div>
            <div x-show="open" @click.away="open = false" class="z-20 origin-top-right absolute left-0 mt-2 rounded-md shadow-lg" style="display: none;">
                <div class="rounded-md bg-white shadow-xs">
                <div class="py-2" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                    @foreach ($submitters as $submitter)
{{-- wire:click="addTodo({{ $todo->id }}, '{{ $todo->name }}')" --}}
                        {{-- <button wire:click="$set('curations_from_submitters', '{{ $submitter->uuid }}')"  --}}
                        <button wire:click="curationsFromSubmitters(['{{ $submitter->uuid }}'])"
                            class="whitespace-no-wrap block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($submitter->uuid, $curations_from_submitters))
                                <i class="fas fa-check-circle"></i>
                            @else
                                <i class="far fa-circle"></i>
                            @endif
                            {{ $submitter->title }}</button>
                    @endforeach
                </div>

                    @if($filtering_by_submitter == true)
                    <div class="py-0" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                        <div class=" text-xs block px-4 py-2 text-sm leading-5 text-gray-700 bg-yellow-300" role="menuitem">
                            You are fitering by submitter. At this time the submission counts reflects all of of the submitters. This feature is on the roadmap.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-4 pr-4">
            <div class='grid grid-cols-9 gap-2 ml-3 invisible xl:visible'>
                @if($curations_definitive == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_definitive', '0')">Definitive</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_definitive', '1')">Definitive</span>
                @endif
                @if($curations_strong == "1")
                    <span class="transform xl:-rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_strong', '0')">Strong</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_strong', '1')">Strong</span>
                @endif
                @if($curations_moderate == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_moderate', '0')">Moderate</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_moderate', '1')">Moderate</span>
                @endif
                @if($curations_supportive == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_supportive', '0')">Supportive</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_supportive', '1')">Supportive</span>
                @endif
                @if($curations_limited == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_limited', '0')">Limited</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_limited', '1')">Limited</span>
                @endif
                @if($curations_disputed == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_disputed', '0')">Disputed</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_disputed', '1')">Disputed</span>
                @endif
                @if($curations_refuted == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_refuted', '0')">Refuted</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_refuted', '1')">Refuted</span>
                @endif
                @if($curations_animal == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_animal', '0')">Animal</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer" wire:click="$set('curations_animal', '1')">Animal</span>
                @endif
                @if($curations_noknown == "1")
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer whitespace-no-wrap" wire:click="$set('curations_noknown', '0')">No Known</span>
                @else
                    <span class="transform -rotate-45 pl-3 text-sm text-gray-600 cursor-pointer whitespace-no-wrap" wire:click="$set('curations_noknown', '1')">No Known</span>
                @endif
            </div>
            <div class='grid grid-cols-9 gap-2 ml-3'>
                @if($curations_definitive == "0")
                    <button class="text-gencc-definitive rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_definitive', '1')" ></button>
                @else
                    <button class="text-gencc-definitive rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-definitive bg-gray-200" wire:click="$set('curations_definitive', '0')" ><i class="fas fa-check"></i></button>
                @endif
                 @if($curations_strong == "0")
                    <button class="text-gencc-strong rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_strong', '1')" ></button>
                @else
                    <button class="text-gencc-strong rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-strong bg-gray-200" wire:click="$set('curations_strong', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_moderate == "0")
                    <button class="text-gencc-moderate rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_moderate', '1')" ></button>
                @else
                    <button class="text-gencc-moderate rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-moderate bg-gray-200" wire:click="$set('curations_moderate', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_supportive == "0")
                    <button class="text-gencc-supportive rounded-full h-6 border-2 mt-1 w-100 border-gray-300 bg-gray-200" wire:click="$set('curations_supportive', '1')" ></button>
                @else
                    <button class="text-gencc-supportive rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-supportive bg-gray-200" wire:click="$set('curations_supportive', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_limited == "0")
                    <button class="text-gencc-limited rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_limited', '1')" ></button>
                @else
                    <button class="text-gencc-limited rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-limited bg-gray-200" wire:click="$set('curations_limited', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_disputed == "0")
                    <button class="text-gencc-disputed rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_disputed', '1')" ></button>
                @else
                    <button class="text-gencc-disputed rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-disputed bg-gray-200" wire:click="$set('curations_disputed', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_refuted == "0")
                    <button class="text-gencc-refuted rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_refuted', '1')" ></button>
                @else
                    <button class="text-gencc-refuted rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-refuted bg-gray-200" wire:click="$set('curations_refuted', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_animal == "0")
                    <button class="text-gencc-animalmodelonly rounded-full h-6 border-2 mt-1 w-100 border-gray-300  bg-gray-200" wire:click="$set('curations_animal', '1')" ></button>
                @else
                    <button class="text-gencc-animalmodelonly rounded-full h-6 border-2 mt-1 w-100  font-bold text-sm border-gencc-animal bg-gray-200" wire:click="$set('curations_animal', '0')" ><i class="fas fa-check"></i></button>
                @endif

                 @if($curations_noknown == "0")
                    <button class="text-gencc-noknown rounded-full h-6 border-2 mt-1 w-100 border-gray-300 bg-gray-200" wire:click="$set('curations_noknown', '1')" ></button>
                @else
                    <button class="text-gencc-noknown rounded-full h-6 border-2 mt-1 w-100 font-bold text-sm border-gencc-noknown bg-gray-200" wire:click="$set('curations_noknown', '0')" ><i class="fas fa-check"></i></button>
                @endif

            </div>
        </div>
            <div class="hidden xl:inline-block xl:visible text-blue-800 pl-0 text-xs mt-6 leading-tight"> <i class="far fa-question-circle"></i> About GenCC <div class="ml-4">Classifications</div></div>
    </div>
    {{-- <div class="row bg-gray-100"> --}}
    <div class="relative row bg-white">
        <div wire:loading class="w-full h-full absolute block top-0 left-0 bg-white opacity-75 z-10">
                <div class="text-center">
                    <i class="fas fa-circle-notch fa-spin fa-10x text-green-600"></i>
                    <div>Loading...</div>
                </div>
        </div>
    @forelse ($genes as $item)

        <div class="row-stripe row-detail border-t-4 border-t-gray-200 border-t-solid py-4"  x-data="{ open: false }">
        <div class="grid grid-cols-12 gap-1">
            <div class="col-span-4 xl:col-span-2 pl-3">
                <a href="{{ route('gene-show', $item->curie) }}" class="list-text-label list-link">
                    {{ $item->title }}
                    <div class="list-text-desc">{{ $item->curie }} <i class="far fa-arrow-alt-circle-right row-detail-button"></i></div>
                </a>
            </div>
            {{-- <div class="col-span-2 text-gray-500">
                <a href="{{ route('gene-show', $item->curie) }}" class="list-text-label">{{ $item->count_unique_diseases}}
                    <div class="list-text-desc truncate pr-4">Submitted Diseases</div>
                </a>
            </div> --}}
            <div class="col-span-4 xl:col-span-3 text-gray-500">
                <span   @click="open = true" class="list-text-label list-link">
                    <span class="list-text-label">{{ $item->count_unique_diseases}}</span>
                    <div class="list-text-desc truncate pr-4">Disease Equivalents <i class="far fa-caret-square-down"></i></div>
                </span>
            </div>
            <div class="col-span-4 xl:col-span-2 text-gray-500">
                <span   @click="open = true" class="list-text-label list-link">
                    {{ $item->count_unique_submitters}}
                    <div class="list-text-desc truncate pr-4">Submitters <i class="far fa-caret-square-down"></i></div>
                </span>
            </div>

            <div class="col-span-12 xl:col-span-4 pr-0">
                <div class='grid grid-cols-9 gap-1'>
                    <div class="col-span-3 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-3 gap-1'>
                            {!! $item->displayCurationCountPill($item->curations_definitive, "definitive", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_strong, "strong", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_moderate, "moderate", route('gene-show', $item->curie)) !!}
                        </div>
                    </div>
                    <div class="col-span-1 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-1 gap-1'>
                            {!! $item->displayCurationCountPill($item->curations_supportive, "supportive", route('gene-show', $item->curie)) !!}
                         </div>
                    </div>
                    <div class="col-span-5 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-5 gap-1'>
                            {!! $item->displayCurationCountPill($item->curations_limited, "limited", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_disputed, "disputed", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_refuted, "refuted", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_animal, "animal-model-only", route('gene-show', $item->curie)) !!}
                            {!! $item->displayCurationCountPill($item->curations_noknown, "no-known-disease-relationship", route('gene-show', $item->curie)) !!}
                        </div>
                    </div>
                    {{-- {!! $item->displayCurationCountPill($item->curations_nul, "nul", route('gene-show', $item->curie)) !!} --}}
                </div>

                {{-- <div class="list-text-desc truncate pt-half">Submitted Classifications</div> --}}
            </div>
            <div class="hidden xl:inline-block invisible xl:visible col-span-1 pr-1 mt-1">
                <a href="{{ route('gene-show', $item->curie) }}" class=" text-center w-100 block border-gray-400 text-gray-500 py-1 mb-1 px-1 rounded-full hover:border-blue-600 hover:text-blue-700 hover:border-2">Details <i class="far fa-arrow-alt-circle-right"></i></a>
            </div>
        </div>

        {{-- TOGGLE OPEN --}}
        <div class="col-span-12" id="ref-{{ $item->curie }}"  x-show="open" @click.away="open = false" style="display: none;">
            <div class='grid grid-cols-12 ml-10 mb-5 border-l-8'>


                    @foreach ($item->displayGeneSubmitterSubmissions($item) as $submitter => $submitter_classifications)
                    <div class="col-span-12 py-2">
                        <hr class="border" />
                    </div>
                    <div class="col-span-12">
                        <div class='grid grid-cols-12 my-1'>
                            <div class="col-span-3 pl-4">
                                <span class=""><i class="far fa-building text-gray-400"></i> <span class="list-text-label">{{ $submitter }}'s </span> submissions</span>
                            </div>
                            <div class="col-span-9">
                                <div class='grid grid-cols-10'>
                                    @foreach ($submitter_classifications as $classification => $classification_diseases)
                                    <div class="col-span-10 py-1">
                                        <div class="flex">
                                            <div class="flex-none">
                                                <span class=" mb-1 inline-block border rounded-full py-1/2 px-3 text-center text-white  whitespace-no-wrap gencc-{{ Str::slug($classification , '') }} ">{{ $classification }}</span>
                                            </div>
                                            <div class="flex-grow pl-1">
                                                @foreach ($classification_diseases as $disease)
                                                    {{-- @isset($disease['diseases']) --}}
                                                    <span class=" py-1/2 px-3 border rounded-full border-gray-300 bg-gray-100 px-2 whitespace-no-wrap mb-1 inline-block">{{ $item->displayDiseaseMondo($disease['diseases'])['title'] }}</span>
                                                    {{-- @endisset --}}
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
            </div>
        </div>
        {{-- TOGGLE END --}}

</div>
        {{-- @livewire('genes.listing-row', compact('item'), key($item->id)) --}}
    @empty
        <div class="border-t border-t-gray-200 border-t-solid pt-2 mt-2">
            <div class="alert alert-info">

            Sorry, we couldn't find anything...
            </div>
        </div>
    @endforelse
    </div>

    {{-- @if(empty($genes)) --}}
        {{ $genes->links() }}
    {{-- @endif --}}
</div>
