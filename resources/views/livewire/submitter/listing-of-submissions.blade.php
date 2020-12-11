<div class="">

      <div class="col-span-12 mb-4"><h2>Submissions </h2>
        <span class="font-bold"> {{ $records->total() }} </span>
        @if($filter_enabled == true)
          submissions based on the filters set below
        @else
          total number of submissions
        @endif

      </div>

      <div class="mt-1 flex rounded-md shadow-sm">
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
                Genes
                <span class="rounded-full text-xs py-1 px-2 leading-tight bg-gray-300">
                @if(count($filter_set['genes']))
                  {{ count($filter['genes']) - count($filter_set['genes']) }} of
                @endif
                {{ count($filter['genes']) }}</span>
                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" style="max-height: 315px;" class=" overflow-y-scroll overscroll-y-contain scrolling-auto z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white  ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                @foreach ($filter['genes'] as $item)
                    <button wire:click="filterByGenes(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['genes']))
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
            <div x-show="open" @click.away="open = false" style="max-height: 315px;" class=" overflow-y-scroll overscroll-y-contain scrolling-auto z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white  ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
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

  <hr class="  mb-6 mt-4" />
    <div class="relative">
              <div wire:loading class="w-full h-full absolute block top-0 left-0 bg-white opacity-75 z-20">
                      <div class="text-center">
                          <i class="fas fa-circle-notch fa-spin fa-10x text-green-600"></i>
                          <div>Loading...</div>
                      </div>
              </div>
              @foreach($records as $item)
                  @include('partials.genes.submission-row-common')
              @endforeach
      {{ $records->links() }}
    </div>
</div>
