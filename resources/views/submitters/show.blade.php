@extends('layouts.app')

@section('headline')
  @include("shared/submitter-headline")
@endsection
@section('content')
<div class="grid grid-cols-2 mt-10 gap-8">
    <div class="col-span-12 xl:col-span-2">
      <h3 class=" truncate">{{ $submitter->title }}</h3>
      <p>This page is a summary of pilot submissions provided by {{ $submitter->title }}. <a class="underline text-blue-600" id="click-signup" href="https://gencc.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank">Click here</a> to be notified about GenCC updates.</p>
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
      <hr class="mt-3 mb-3 border" />
      <strong>Personnel</strong>
      <div class="mb-2">
        {!! $submitter->text_contact !!}
      </div>
        @isset($submitter->text_assertions)
        <hr class="mt-3 mb-3 border" />
        <strong>Assertion Criteria</strong>
        <div class="mb-2">
          @foreach (explode('<br>' ,$submitter->text_assertions) as $item)
              <div class="truncate"><a class="text-blue-700 underline" href="{{ $item }}">{{ $item }} <i class="fas fa-external-link-alt"></i></a></div>
          @endforeach
        </div>
        @endisset

    </div>
    @if($submitter->count_submissions != 0)
    <div class="col-span-12 xl:col-span-1 bg-gray-200 p-3">

      <h4 class="mb-1">Classifications Visualized</h4>
        <hr class="mt-1 mb-0 border" />
  <div class="grid grid-cols-12 gap-0 text-sm">

    @foreach ($classifications as $item)
    @if($item->curie != "GENCC:000000")
      <div class="col-span-3 border-r-2 border-gray-300 py-1 px-2">
        <div class="rounded-full py-1 px-1 text-right  leading-tight">
          <a href="{{ route('genes') }}?{{ $item->href }}=1">
            {{ $item->title }}
          </a>
        </div>
      </div>
      <div class="col-span-9 py-1 px-2">
        @if( $item->displayStatChartBarPercentSubmitter($submitter, $item->href) != 0)
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0" class="inline-block rounded-full px-3 text-right py-1 text-white {{ $item->css_class }}" style="width:{{ $item->displayStatChartBarPercentSubmitter($submitter, $item->href) }}%">
          &nbsp;
        </a>
        <a href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{ $item->displayStatChartBarPercentSubmitter($submitter, $item->href, 'count') }}</span> Submissions
          </a>
        @else
        <a class="pt-1 inline-block" href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{ $item->displayStatChartBarPercentSubmitter($submitter, $item->href, 'count') }} </span> Submissions
          </a>
        @endif
      </div>
      @endif
    @endforeach
    </div>
    </div>
    @else
            <p class="mb-8 text-sm leading-5 text-center font-medium text-gray-500">
                Submission Coming Soon
            </p>
          @endif



</div>
<div class="grid grid-cols-12 mt-10 gap-0">
    <div class="col-span-12">
        @if($submitter->count_submissions != 0)
            @livewire('submitter.listing-of-submissions', ['submitter' => $submitter])

          @endif
      </div>
</div>


@endsection
