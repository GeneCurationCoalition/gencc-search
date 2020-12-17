@extends('layouts.app')

@section('headline')
  @include("shared/submission-headline")
@endsection
@section('content')
<div class="grid grid-cols-12 mt-4 gap-0">
    <div class="col-span-12">
      <div class="grid grid-cols-12 gap-0">


        <div class="col-span-2 pt-3 text-right pr-3">Submitter:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal font-bold"><a class="underline" href="{{ route('submitter-show', $submission->submitter->uuid) }}">{{ $submission->submitter->title }}</a></div>
          {{-- <div class="text-xs">{{ $submission->submitter->curie }}</div> --}}
        </div>

        {{-- <div class="col-span-2 pt-3 text-right pr-3">GenCC Submission ID:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal font-bold">{{ $submission->uuid }}</a></div>
        </div> --}}

        <hr class="col-span-12 my-4" />

        <div class="col-span-2 pt-3 text-right pr-3">Classification:</div>
        <div class="col-span-4 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal ">
            {!!  $submission->displayCurationLabelPill($submission->classification) !!}</div>
          <div class="text-xs">{{ $submission->classification->curie }}</div>
        </div>
        <div class="col-span-12"></div>

        <div class="col-span-2 pt-3 text-right pr-3">Gene:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          @if($submission->gene)
          <div class="font-normal"><a class="underline" href="{{ route('gene-show', $submission->gene->uuid) }}">{{ $submission->gene->title }}</a></div>
          <div class="text-xs">{!! $submission->displayLinkToHgnc($submission->gene->curie, $submission->gene->curie) !!}</div>
          @else
            <div class="font-normal">N/A</div>
          @endif
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Disease:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="mb-2">
              <div class="font-normal">{{ $submission->disease->title }}</div>
              <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease->curie, $submission->disease->curie) !!}</div>
            </div>
            @if($submission->disease->id != $submission->disease_original->id)
            <div class="mb-2">
              <div class="font-normal">{{ $submission->disease_original->title }}</div>
                <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease_original->curie, $submission->disease_original->curie) !!}</div>
            </div>
            @endif
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Mode Of Inheritance:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ $submission->inheritance->title }}</div>
          <div class="text-xs">{!! $submission->displayLinkToMoi($submission->inheritance->curie, $submission->inheritance->curie) !!}</div>
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Evaluated Date:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ Carbon\Carbon::parse($submission->submitted_as_date)->format('m/d/Y') }}</div>
        </div>

        @if (strlen($submission->submitted_as_notes)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Evidence/Notes:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">

            <div class="font-normal">{{ $submission->submitted_as_notes }}</div>

        </div>
        @endif


          @if (strlen($submission->submitted_as_pmids)>2)
        <div class="col-span-2 pt-3 text-right pr-3">PubMed IDs:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal">{{ $submission->submitted_as_pmids }}</div>

        </div>
         @endif

          @if(strlen($submission->submitted_as_public_report_url)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Public Report:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal"><a class="underline"  id='click-exit-public-report' target="_blank" href="{{  $submission->submitted_as_public_report_url }}">Click here to view the public report <i class="fas fa-external-link-alt"></i></a></div>
            <div class="text-xs"><a class="" id='click-exit-public-report'  target="_blank" href="{{  $submission->submitted_as_public_report_url }}">{{ $submission->submitted_as_public_report_url }} <i class="fas fa-external-link-alt"></i></a></div>


        </div>
         @endif

          @if(strlen($submission->submitted_as_assertion_criteria_url)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Assertion Criteria:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal"><a class="underline" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">Click here to view assertion criteria <i class="fas fa-external-link-alt"></i></a></div>
            <div class="text-xs"><a class="" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">{{ $submission->submitted_as_assertion_criteria_url }} <i class="fas fa-external-link-alt"></i></a></div>


        </div>
         @endif

        {{-- <div class="col-span-2 pt-3 text-right pr-3 pb-3">Submission ID from Submitter:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="">{{ $submission->submitted_as_submission_id }}</div>

        </div> --}}
        <div class="col-span-2 pt-3 text-right pr-3">Submitter Submitted Date:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="">@if($submission->submitted_run_date) {{ Carbon\Carbon::parse($submission->submitted_run_date)->format('m/d/Y') }} @else N/A @endif</div>

        </div>

      </div>
    </div>
</div>


@endsection
