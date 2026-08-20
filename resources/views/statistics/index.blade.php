@extends('layouts.app')
@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Statistics</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" target="_blank" href="{{ route('faq') }}"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
<div class="mt-2">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-2 xl:gap-10 mb-6">
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
          <a href="{{ route('members') }}">
            <div class="text-2xl xl:text-6xl mb-0 pb-0 leading-none">{{ $submitters_with_submissions->count() }}</div>
            <i class="fas fa-disease"></i> Submitters with submissions
            <div class="underline">Learn about GenCC's submitters</div>
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
          <a href="{{ route('genes') }}?{{ $item->only_filter_query }}">
            {{ $item->title }}
          </a>
        </div>
      </div>
      <div class="col-span-8 xl:col-span-9 py-1 px-2">
        @if( $item->displayStatChartBarPercent($submissionsCount, $item->submissions_count) != 0)
        <a href="{{ route('genes') }}?{{ $item->only_filter_query }}" class="inline-block rounded-full px-3 text-right py-0 text-white {{ $item->css_class }}" style="width:{{ $item->displayStatChartBarPercent($submissionsCount, $item->submissions_count) }}%">
          &nbsp;
        </a>
        <a href="{{ route('genes') }}?{{ $item->only_filter_query }}">
            <span class="font-bold">{{ $item->submissions_count }} </span> Submissions
          </a>
        @else
        <a class="pt-1 inline-block" href="{{ route('genes') }}?{{ $item->only_filter_query }}">
            <span class="font-bold">{{ $item->submissions_count }} </span> Submissions
          </a>
        @endif
      </div>
      @endif
    @endforeach
  </div>
</div>
<div class="col-12 mt-10"><hr /></div>
<div class="mt-10">
  <h2 class="my-3">Classifications Visualized by Gene</h2>
  <p class="mb-4 text-sm text-gray-600">
    Each gene is counted once, in the bucket for its strongest assertion. A gene
    with Definitive, Strong and Moderate assertions appears only under Definitive.
    Supportive is counted only where it is a gene’s sole assertion.
  </p>
  <div class="grid grid-cols-12 gap-0">
    @foreach ($genesByClassification as $row)
      @php($item = $row['classification'])
      @php($geneCount = $row['genes_count'])
        <div class="col-span-4 xl:col-span-2 border-r-2 border-gray-300 py-2 px-2">
          <div class="rounded-full py-1 px-1 text-right leading-tight">
            <a href="{{ route('genes') }}?{{ $item->only_filter_query }}">
              {{ $item->title }}
            </a>
          </div>
        </div>
        <div class="col-span-8 xl:col-span-9 py-1 px-2">
          @if($geneCount != 0)
            <a href="{{ route('genes') }}?{{ $item->only_filter_query }}" class="inline-block rounded-full px-3 text-right py-0 text-white {{ $item->css_class }}" style="width:{{ $item->displayStatChartBarPercent($genesByClassificationTotal, $geneCount) }}%">
              &nbsp;
            </a>
          @endif
          <a @class(['pt-1 inline-block' => $geneCount == 0]) href="{{ route('genes') }}?{{ $item->only_filter_query }}">
            <span class="font-bold">{{ $geneCount }} </span> Genes
          </a>
        </div>
    @endforeach
  </div>
</div>
<div class="col-12 mt-10"><hr /></div>
<div class="mt-10">
  <h2 class="my-3">GenCC Submitters Stats</h2>
  @include('partials.submitter.submitter-grid')
</div>

@endsection
