<div class="">

      <div class="col-span-12 mb-4"><h3>Submissions </h3>
        <span class="font-bold"> {{ $records->total() }} </span>
        @if($filter_enabled == true)
          submissions based on the filters set below
        @else
          total number of submissions
        @endif

      </div>

      <div class="mt-1 flex flex-col lg:flex-row rounded-md shadow-sm">


        @if($records->count())
          <div class="relative inline-block text-left flex-1 block w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 text-gray-700 input-text hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                Classifications

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


          <div class="col-span-4 xl:col-span-2 ">
            <input class="input input-text" wire:model.debounce.500ms="query_gene" type="text" value="{{ $query_gene }}" placeholder="Filter by gene symbol...">
          </div>
          <div class="col-span-4 xl:col-span-2 ">
            <input class="input input-text" wire:model.debounce.500ms="query_disease" type="text" value="{{ $query_disease }}" placeholder="Filter by disease name...">
          </div>


          <div class="relative inline-block text-left flex-1 w-full " x-data="{ open: false }">
            <div  @click="open = true">
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 input-text text-gray-700 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                MOI

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
              <button type="button" class=" text-left inline-flex w-full border border-gray-300 px-4 py-2 bg-white leading-5 input-text text-gray-700 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
                Status

                <i class="fas fa-angle-down ml-1"></i>
              </button>
            </div>
            <div x-show="open" @click.away="open = false" class="z-10 origin-top-left absolute left-0 mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100" style="display: none;">
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                <button wire:click="filterByStatus(['0','1','2'])"
                            class="whitespace-no-wrap border border-b w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(!count($filter_set['status']))
                                <i class="fas fa-check-circle"></i>
                            @else
                                <i class="far fa-circle"></i>
                            @endif
                            All </button>
                @foreach ($filter['status'] as $item)
                    <button wire:click="filterByStatus(['{{ $item['ref'] }}'])"
                            class="whitespace-no-wrap w-full text-left block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900" role="menuitem">
                            @if(in_array($item['ref'], $filter_set['status']))
                                <i class="fas fa-check-circle"></i>
                            @else
                                <i class="far fa-circle"></i>
                            @endif
                            {{ $item['title'] }}</button>
                @endforeach
              </div>
            </div>

          </div>

          @else


            <a href="{{ route('manage-submitter-show', $submitter_curie) }}" class="text-blue-500">Reset Filters... (Or it may be they do not have any submissions yet)</a>

          @endif




      </div>

  <hr class="  mb-6 mt-4 " />
    <div class="relative min-h-screen">
              <div wire:loading class="w-full h-full absolute block top-0 left-0 bg-white opacity-75 z-20">
                      <div class="text-center">
                          <i class="fas fa-circle-notch fa-spin fa-10x text-green-600"></i>
                          <div>Loading...</div>
                      </div>
              </div>
              @foreach($records as $item)
                  @include('partials.dashboard.manage-submission-row-common')
              @endforeach
      {{ $records->links() }}
    </div>
</div>
