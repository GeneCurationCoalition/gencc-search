@extends('layouts.app')
@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Statistics</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
<div class="mt-2">
  <div class="grid grid-cols-3 gap-2 xl:gap-10 mb-6">
        <div class="rounded-full py-5 text-xs px-1 text-center text-blue-800 border-solid border-8 border-blue-800 bg-gray-200">
          <a href="{{ route('genes') }}">
            <div class="text-2xl xl:text-6xl mb-0 pb-0 leading-none">{{ $submissionsCount }}</div>
            <i class="fas fa-file-medical-alt"></i> Submitted Classifications
          </a>
        </div>
        <div class="rounded-full py-5 text-xs px-1 text-center text-blue-800 border-solid border-8 border-blue-800 bg-gray-200">
          <a href="{{ route('genes') }}">
            <div class="text-2xl xl:text-6xl mb-0 pb-0 leading-none">{{ $genesCount }}</div>
            <i class="fas fa-dna"></i> Unique Genes with Submissions
          </a>
        </div>
        <div class="rounded-full py-5 text-xs px-1 text-center text-blue-800 border-solid border-8 border-blue-800 bg-gray-200">
          <a href="{{ route('submitters') }}">
            <div class="text-2xl xl:text-6xl mb-0 pb-0 leading-none">{{ $submitter->count() }}</div>
            <i class="fas fa-disease"></i> Submitters with submissions
          </a>
        </div>
  </div>
</div>
@endsection
@section('content')
<div class="mt-6">
  <h2 class="my-3">Classifications Visualized</h2>
  <div class="grid grid-cols-12 gap-0">
    @foreach ($classifications as $item)
    @if($item->curie != "GENCC:000000")
      <div class="col-span-4 xl:col-span-2 border-r-2 border-gray-300 py-2 px-2">
        <div class="rounded-full py-1 px-1 text-right  leading-tight">
          <a href="{{ route('genes') }}?{{ $item->href }}=1">
            {{ $item->title }}
          </a>
        </div>
      </div>
      <div class="col-span-8 xl:col-span-9 py-1 px-2">
        @if( $item->displayStatChartBarPercent($submissionsCount, $item->submissions->count()) != 0)
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0" class="inline-block rounded-full px-3 text-right py-0 text-white {{ $item->css_class }}" style="width:{{ $item->displayStatChartBarPercent($submissionsCount, $item->submissions->count()) }}%">
          &nbsp;
        </a>
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{  $item->submissions->count() }} </span> Submissions
          </a>
        @else
        <a class="pt-1 inline-block" href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{  $item->submissions->count() }} </span> Submissions
          </a>
        @endif
      </div>
      @endif
    @endforeach
  </div>
</div>
<div class="col-12 mt-10"><hr /></div>
<div class="mt-10">
  <h2 class="my-3">Classification Facts</h2>
  <div class="grid grid-cols-3  gap-2 xl:gap-10">
    @foreach ($classifications as $item)
      @if($item->curie != "GENCC:000000")
        <div class="rounded-full py-5 text-xs px-1 text-center text-white {{ $item->css_class }}">
          <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <div class="text-2xl xl:text-6xl mb-0 pb-0 leading-none">{{ $item->submissions->count() }}</div>
            # {{ $item->title }} classifications
          </a>
        </div>
      @endif
    @endforeach
  </div>
</div>

@endsection
