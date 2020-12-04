@extends('layouts.app')

@section('headline')
  @include("shared/submitter-headline")
@endsection
@section('content')
<div class="grid grid-cols-2 mt-10 gap-8">
    <div class="col-span-12 xl:col-span-2">
      <h3 class=" truncate">{{ $submitter->title }}</h3>
      <div class="text-sm">GenCC Ref: {{ $submitter->curie }}</div>
      {{-- <hr class="mt-4 border" /> --}}
    </div>
    <div class="col-span-12 xl:col-span-1 py-3">
      <p>Per in ridens elaboraret. Ne vix amet maluisset, sed te eros harum expetenda, in eos atqui adversarium. Nibh scaevola signiferumque est in. Vim at impetus consequuntur, tota inimicus expetendis et sed. Eius accusata pro eu.</p>
      @if($submitter->website)
      <hr class="mt-3 mb-3 border" />
      <strong>Website</strong>
      <div class="mb-1"><a href="{{ $submitter->website }}" target="_website" >{{ $submitter->website }} <i class="fas fa-external-link-alt"></i></a></div>
      @endif
      <hr class="mt-3 mb-3 border" />
      <strong>Personnel</strong>
      <div class="mb-2">John XXXXXX, Coordinator<br />
          Phone: 800-XXX-XXXXXX<br />
          Email: XXXXX.XXXXXXX@XXXXXXX.com
      </div>
        <hr class="mt-3 mb-3 border" />
        <strong>Assertion Criteria</strong>
        <div class="mb-1">website link here.... <i class="fas fa-external-link-alt"></i></div>
        <div class="mb-1">website link here.... <i class="fas fa-external-link-alt"></i></div>

    </div>
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
            <span class="font-bold">{{ $item->displayStatChartBarPercentSubmitter($submitter, $item->href, 'count') }}</span> Curations
          </a>
        @else
        <a class="pt-1 inline-block" href="{{ route('genes') }}?curations_definitive=1&curations_strong=1&curations_moderate=1&curations_limited=1&curations_disputed=1&curations_refuted=1&curations_animal=1&curations_noknown=1&{{ $item->href }}=0">
            <span class="font-bold">{{ $item->displayStatChartBarPercentSubmitter($submitter, $item->href, 'count') }} </span> Curations
          </a>
        @endif
      </div>
      @endif
    @endforeach
  </div>

    </div>
</div>
<div class="grid grid-cols-12 mt-10 gap-0">
    <div class="col-span-12">


            @livewire('submitter.listing-of-submissions', ['submitter' => $submitter])


              {{-- @foreach($submitter->submissions as $item)
                  <div class="border-t border-t-gray-200 border-t-solid pt-0 mt-2 text-sm">
                      <div class="grid grid-cols-10 gap-2 my-3 ">

                          <div class="col-span-1 -mt-1"><a href="{{ route('submission-show', $item->uuid) }}">{!!  $item->displayCurationLabelPill($item->classification) !!}</a></div>
                          <div class="col-span-3  ml-0 mr-3">
                            <div class="flex">
                              <div class="flex-initial mr-1 leading-tight">
                                <i class="fas fa-dna text-gray-400"></i>
                              </div>
                              <div class="flex-initial leading-tight">
                                @if($item->gene)
                                <a class="list-text-label" href="{{ route('gene-show', $item->gene->uuid) }}"> {{ $item->gene->title }}</a>
                                <div class="text-sm text-gray-600">{{ $item->gene->curie }}</div>
                                @endif
                              </div>
                              <div class="flex-initial ml-4 mr-1">
                                <i class="far fa-disease text-gray-400"></i>
                              </div>
                              <div class="flex-initial break-words leading-tight">
                                <span class="list-text-label">{{ $submitter->displayMondoDisease($item->diseases)->first()->title }}</span>
                                <div class="text-sm text-gray-600">{{ $submitter->displayMondoDisease($item->diseases)->first()->curie }}</div>
                                <div class="mt-1 text-sm text-gray-600"> Submitted as: {{ $item->disease->curie }}</div>
                              </div>
                            </div>

                          </div>
                          <div class="col-span-1"><span class="list-text-label"> {{ $item->inheritance->abbreviation }} <i class="far fa-question-circle text-gray-400" title="{{ $item->inheritance->title }} Mode Of Inheritance " data-toggle="tooltip" data-placement="top" \></i></div>
                          <div class="col-span-1"><i class="far fa-calendar text-gray-400"></i> <span class="list-text-label">{{ Carbon\Carbon::parse($item->submitted_as_date)->format('m/d/Y') }}</span><div class="text-sm text-gray-600 ml-4">Evaluated</div>
                        <div class="text-sm mt-3 text-gray-600 ml-4 font-semibold leading-snug">{{ $item->submitted_run_date->format('m/d/Y') }}<div class=" font-normal">Submitted</div></div>
                      </div>
                          <div class="col-span-4">

                            <a href="{{ route('submitter-show', $item->submitter->uuid) }}" class=""><i class="far fa-building text-gray-400"></i> <span class="list-text-label">{{ $item->submitter->title }}</span></a>
                            <div class="ml-4 pt-1 text-sm flex-inline-list">
                              <ul>
                                @if($item->submitted_as_public_report_url)
                              <li><a target="assertion_criteria_url" href="{{ $item->submitted_as_public_report_url }}" class="text-blue-600">Public Report <i class="fas fa-external-link-alt"></i></a></li>
                                @endif
                                @if($item->submitted_as_assertion_criteria_url)
                              <li><a target="assertion_criteria_url" href="{{ $item->submitted_as_assertion_criteria_url }}" class="text-blue-600">Assertion Criteria <i class="fas fa-external-link-alt"></i></a></li>
                                @endif
                              <li><a class="text-blue-600" href="{{ route('submission-show', $item->uuid) }}">More Details <i class="far fa-arrow-alt-circle-right"></i></a></li>
                              </ul>
                            </div>
                            @if($item->submitted_as_notes)
                            <div class="ml-4 pt-1 text-sm text-gray-800">Evidence: {!! \Illuminate\Support\Str::limit($item->submitted_as_notes, 100, $end='... <a class="text-gray-600 underline" href="'. route('submission-show', $item->uuid) .'">Read more <i class="far fa-arrow-alt-circle-right"></i></a>') ?? ''!!}</div>
                            @endif

                          </div>
                          <div class="col-span-1 text-right"></div>
                      </div>
                  </div>
              @endforeach --}}
      </div>
</div>


@endsection
