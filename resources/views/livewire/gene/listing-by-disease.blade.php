<div>
      <div class="mt-1 flex flex-col lg:flex-row rounded-md shadow-sm">
          <span class="inline-flex shadow items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
            Filters:
          </span>


          <div class="relative inline-block text-left flex-1 block w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                Classifications
                <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                @if(count($filter_set['classifications']))
                  {{ count($filter['classifications']) - count($filter_set['classifications']) }} of
                @endif
                {{ count($filter['classifications']) }}</span>
                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white  ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                @foreach ($filter['classifications'] as $item)
                    <button wire:click="filterByClassifications(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['classifications']))
                                <i class="far fa-circle"></i>
                            @else
                                <i class="fas fa-check-circle"></i>
                            @endif
                            {{ $item['title'] }}</button>
                @endforeach
              </div>
            </div>
          </div>


          <div class="relative inline-block text-left flex-1 w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                Diseases
                <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                @if(count($filter_set['diseases']))
                  {{ count($filter['diseases']) - count($filter_set['diseases']) }} of
                @endif
                {{ count($filter['diseases']) }}</span>
                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white  ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                @foreach ($filter['diseases'] as $item)
                    <button wire:click="filterByDiseases(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['diseases']))
                                <i class="far fa-circle"></i>
                            @else
                                <i class="fas fa-check-circle"></i>
                            @endif
                            {{ $item['title'] }}</button>
                @endforeach
              </div>
            </div>
          </div>


          <div class="relative inline-block text-left flex-1 w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 input-text text-gray-700 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                MOI
                <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                @if(count($filter_set['inheritances']))
                  {{ count($filter['inheritances']) - count($filter_set['inheritances']) }} of
                @endif
                {{ count($filter['inheritances']) }}</span>
                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                @foreach ($filter['inheritances'] as $item)
                    <button wire:click="filterByInheritances(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['inheritances']))
                                <i class="far fa-circle"></i>
                            @else
                                <i class="fas fa-check-circle"></i>
                            @endif
                            {{ $item['title'] }}</button>
                @endforeach
              </div>
            </div>
          </div>


          <div class="relative inline-block text-left flex-1 w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full rounded-r-md border border-gray-300 px-4 py-2 bg-white input-text text-gray-700 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                Submitters
                <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                @if(count($filter_set['submitters']))
                  {{ count($filter['submitters']) - count($filter_set['submitters']) }} of
                @endif
                {{ count($filter['submitters']) }}</span>
                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                @foreach ($filter['submitters'] as $item)
                    <button wire:click="filterBySubmitters(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['submitters']))
                                <i class="far fa-circle"></i>
                            @else
                                <i class="fas fa-check-circle"></i>
                            @endif
                            {{ $item['title'] }}</button>
                @endforeach
              </div>
            </div>
          </div>



      </div>

  <hr class="mb-6 mt-4" />

    @foreach ($filter['diseases'] as $row)
          @php $render = false @endphp
          <div class="grid grid-cols-12 gap-2 mb-6">
            <div class="col-span-12">
            <h3 class="m-0 p-0 leading-none">{{ $row['title'] }} classifications
              {{-- {!! $gene->displayCurationLabelPill($record, count($gene->displayGroupSubmissionsByClassification($gene->submissions, $record)), 'count') !!}  --}}
            </h3>
              @foreach($records as $item)
              @if($item->disease->uuid == $row['uuid'])
                  @include('partials.genes.submission-row-common')
                  @php $render = true @endphp
              @endif
              @endforeach
              @if($render != true)
                <!-- This example requires Tailwind CSS v2.0+ -->
                <div class="rounded-md bg-gray-200 p-3 mt-1">
                    <div class="ml-3">
                      <p class=" text-gray-600">
                        The filters set above are suppressing entries...
                      </p>
                    </div>
                </div>
              @endif
            </div>
          </div>
      @endforeach
</div>
