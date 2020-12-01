<div class="grid grid-cols-12 gap-0">
      <div class="col-span-2 text-blue-200">Gene Symbol:</div>
      <div class="col-span-10">
        <div class="font-normal text-white font-bold">{{ $gene->title }}</div>
          <div class="text-xs">{{ $gene->curie }}</div>
      </div>
      @if($gene->locus_group)
      <div class="col-span-12 my-1"></div>
      <div class="col-span-2 text-blue-200">Locus Group:</div>
      <div class="col-span-9">
        <div class="font-normal text-white capitalize">{{ $gene->locus_group }}</div>
      </div>
      @endif
      @if($gene->locus_type)
      <div class="col-span-12 my-1"></div>
      <div class="col-span-2 text-blue-200">Locus Type:</div>
      <div class="col-span-9">
        <div class="font-normal text-white capitalize">{{ $gene->locus_type }}</div>
      </div>
      @endif
      @if($gene->location)
      <div class="col-span-12 my-1"></div>
      <div class="col-span-2 text-blue-200">Location:</div>
      <div class="col-span-9">
        <div class="font-normal text-white capitalize">{{ $gene->location }}</div>
      </div>
      @endif
    <div class="col-span-12 mb-0 mt-6">
    <ul class="flex border-b border-blue-700 tabs">
      {{-- <li class="-mb-px mr-3 mt-2 text-gray-500">
        Submissions organized by:
      </li> --}}
      <li class="tab @if($page == 'show') active @endif">
        <a class="tab-text @if($page == 'show') active @endif" href="{{ route("gene-show", $gene->curie) }}">By Classification
          {{-- <span class="tab-pill">{{ count($gene->submissions) }}</span> --}}
        </a>
      </li>
      <li class="tab @if($page == 'disease') active @endif">
        <a class="tab-text" href="{{ route("gene-show-disease", $gene->curie) }}">By Disease
          {{-- <span class="tab-pill">{{ count($gene->submissions) }}</span> --}}
        </a>
      </li>
      <li class="tab  @if($page == 'submitter') active @endif">
        <a class="tab-text" href="{{ route("gene-show-submitter", $gene->curie) }}">By Submitter
          {{-- <span class="tab-pill">{{ count($gene->submissions) }}</span> --}}
        </a>
      </li>
      {{-- <li class="tab">
        <a class="tab-text" href="#">Tab</a>
      </li> --}}
    </ul>
    </div>
  </div>