@extends('layouts.app')

@section('headline')
  @include("shared/gene-headline")
@endsection
@section('banner')
  @include("shared/gene-banner")
@endsection
@section('content')
<div class="grid grid-cols-12 mt-6 gap-0">
    {{-- <div class="col-span-2 pr-4">

      <div class="border-b border-gray-300 text-gray-600">Diseases Display </div>
      <div class='text-sm col-12 my-2 text-gray-700'><span class='float-right text-gray-600 px-1 rounded-full border text-xs border-gray-300 bg-gray-200'>1</span><i class='far fa-square text-gray-300'></i> Submitted </div>
      <div class='text-sm col-12 my-2'><i class="far fa-check-square text-green-700"></i> Equivalents <span class='float-right text-gray-600 px-1 rounded-full border text-xs border-gray-300 bg-gray-200'>1</span></div>

      <div class="border-b border-gray-300 text-gray-600 mt-8">Classification Types</div>
      @foreach ($records as $record)
      {!! $gene->displayCurationLabelPill($record, count($gene->displayGroupSubmissionsByClassification($gene->submissions, $record)), 'filter') !!}
      @endforeach

      <div class="border-b border-gray-300 text-gray-600 mt-8">Filter Diseases</div>
            <input class="input input-text" wire:model="title" type="text" value="" placeholder="Disease...">

      <div class="border-b border-gray-300 text-gray-600 mt-8">Submitters</div>
      <div class='text-sm col-12 my-2 text-gray-700'><i class='far fa-check-square  text-green-700'></i> ClinGen <span class='float-right text-gray-600 px-1 rounded-full border text-xs border-gray-300 bg-gray-200'>1</span></div>
      <div class='text-sm col-12 my-2 text-gray-700'><i class='far fa-check-square  text-green-700'></i> Placeholder <span class='float-right text-gray-600 px-1 rounded-full border text-xs border-gray-300 bg-gray-200'>1</span></div>



    </div> --}}
    <div class="col-span-12">

        @livewire('gene.listing-by-classification', ['gene' => $gene])

        {{-- @foreach ($records as $record)
        @if($record->submissions->count())
          <div class="grid grid-cols-12 gap-2 mb-6">
            <div class="col-span-12">
            <h3 class="m-0 p-0 leading-none">{{ $record->title }} classifications
            </h3>
              @foreach($record->submissions as $item)
                  @include('partials.genes.submission-row-common')
              @endforeach
            </div>
          </div>
          @endif
        @endforeach --}}
      </div>
</div>


@endsection
