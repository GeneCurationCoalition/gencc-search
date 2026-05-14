@extends('layouts.app')

@section('headline')
  @include("shared/submitter-headline")
@endsection
@section('content')
<div class="grid grid-cols-2 mt-10 gap-8">
    <div class="col-span-12 xl:col-span-2">
      <h3 class=" truncate">{{ $submitter->title }}</h3>
      <p>This page is a summary of submissions provided by {{ $submitter->title }}. <a class="underline text-blue-600" id="click-signup" href="https://creationproject.us7.list-manage.com/subscribe?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank">Click here</a> to be notified about GenCC updates.</p>
      {{-- <hr class="mt-4 border" /> --}}
    </div>
    <div class="col-span-12 xl:col-span-1 py-3">
      <p>{!! $submitter->text_descriptions !!}</p>
      @if($submitter->website)
      <hr class="mt-3 mb-3 border" />
      <strong>Website</strong>
      <div class="mb-1">
        <a href="{{ $submitter->website }}" id="click-submitter-website" class="text-blue-700 underline" target="_blank" >{{ $submitter->website }} <i class="fas fa-external-link-alt"></i></a></div>
      @endif
      @if(!($submitter->member == 1 && empty($submitter->text_contact)))
      <hr class="mt-3 mb-3 border" />
      <strong>Contact</strong>
      <div class="mb-2">
        {!! $submitter->text_contact !!}
      </div>
      @endif
      @if($submitter->assertion)
      <hr class="mt-3 mb-3 border" />
      <strong>Assertion Criteria</strong>
      <div class="mb-2 assertion-content">
        {!! Str::markdown($submitter->assertion) !!}
      </div>
      <style>
        .assertion-content a { color: #1d4ed8; text-decoration: underline; }
        .assertion-content a:hover { color: #1e40af; }
        .assertion-content p { margin-bottom: 0.5rem; }
        .assertion-content ul, .assertion-content ol { margin-left: 1.5rem; margin-bottom: 0.5rem; }
        .assertion-content li { margin-bottom: 0.25rem; }
      </style>
      @endif

    </div>
    @if(!$submitter->allow_submissions)
            {{-- No submissions and allow_submissions=false --}}
    <div class="col-span-12 xl:col-span-1">
      <div class="flex flex-col items-center justify-center">
        @if($submitter->has_logo)
        <img class="h-32 max-w-md object-contain mb-4" src="{{ $submitter->logo }}" alt="{{ $submitter->title }}">
        @endif
        <p class="text-sm leading-5 text-center font-medium text-gray-500">
            Member
        </p>
      </div>
    </div>
    @elseif($countSummary === null)
    <div class="col-span-12 xl:col-span-1">
      <p class="mb-8 text-sm leading-5 text-center font-medium text-gray-500">
          Submission counts unavailable
      </p>
    </div>
    @elseif($submitterSubmissionsCount > 0)
    <div class="col-span-12 xl:col-span-1 bg-gray-200 p-3">

      <h4 class="mb-1">Classifications Visualized</h4>
        <hr class="mt-1 mb-0 border" />
  <div class="grid grid-cols-12 gap-0 text-sm">

    @foreach ($classifications as $item)
    @if($item->curie != "GENCC:000000")
      @php
        $classificationCount = (int) $classificationCounts->get($item->id, 0);
        $classificationPercent = $submitterSubmissionsCount > 0
            ? ($classificationCount / $submitterSubmissionsCount) * 100
            : 0;
      @endphp
      <div class="col-span-3 border-r-2 border-gray-300 py-1 px-2">
        <div class="rounded-full py-1 px-1 text-right  leading-tight">
          <a href="{{ route('genes') }}?{{ $item->href }}=1">
            {{ $item->title }}
          </a>
        </div>
      </div>
      <div class="col-span-9 py-1 px-2">
        @if($classificationCount != 0)
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0" class="inline-block rounded-full px-3 text-right py-1 text-white {{ $item->css_class }}" style="width:{{ $classificationPercent }}%">
          &nbsp;
        </a>
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{ $classificationCount }}</span> Submissions
          </a>
        @else
        <a class="pt-1 inline-block" href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{ $classificationCount }} </span> Submissions
          </a>
        @endif
      </div>
      @endif
    @endforeach
    </div>
    </div>
    @else
            {{-- No submissions, but allow_submissions=true --}}
            <p class="mb-8 text-sm leading-5 text-center font-medium text-gray-500">
                No submissions
            </p>
    @endif



</div>
<div class="grid grid-cols-12 mt-10 gap-0">
    <div class="col-span-12">
        @if($countSummary !== null && $submitterSubmissionsCount > 0)
            @livewire('submitter.listing-of-submissions', ['submitter' => $submitter])

          @endif
      </div>
</div>


@endsection
