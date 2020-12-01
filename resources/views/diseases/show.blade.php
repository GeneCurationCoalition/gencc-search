@extends('layouts.app')

@section('content')
<div class="grid grid-cols-12 gap-0">
  <div class="col-span-10 text-gray-600"><h1 class=" truncate">{{ $disease->title }}</h1></div>
      <div class="col-span-2 pt-6">
        <div class="text-right text-xl"><a href="{{ route("diseases") }}" class="rounded-full py-1 px-5 text-center border-2 border-solid border-blue-600 text-blue-600 whitespace-no-wrap"><i class="far fa-arrow-alt-circle-left"></i> Return to list</a></div>
      </div>
    <div class="col-span-12 mb-4 mt-4"><hr /></div>
      <div class="col-span-2 text-gray-600">Disease Name:</div>
      <div class="col-span-10">
        <div class="font-normal text-gray-600 font-bold">{{ $disease->title }}</div>
          <div class="text-xs">{{ $disease->curie }}</div>
      </div>
      @if($disease->description)
      <div class="col-span-12 my-2"></div>
      <div class="col-span-2 text-gray-600">Description:</div>
      <div class="col-span-9">
        <div class="font-normal text-gray-600 capitalize">{{ $disease->description }}</div>
      </div>
      @endif
    <div class="col-span-12 mb-12 mt-8"><hr class="border-4" /></div>
      <div class="col-span-2 text-gray-600">Classifications:</div>
      <div class="col-span-10">
        @foreach ($classifications as $classification)
          <div class="grid grid-cols-12 gap-4 mb-6">
            <div class="col-span-1">{!! $disease->displayCurationLabelPill($classification, count($disease->displayGroupSubmissionsByClassification($disease->submissions, $classification)), 'count') !!}</div>
            <div class="col-span-11">
            <h3 class="m-0 p-0 leading-none">{{ $classification->title }}</h3>
              @foreach($disease->displayGroupSubmissionsByClassification($disease->submissions, $classification) as $item)
                  <div class="border-t border-t-gray-200 border-t-solid pt-0 mt-2">
                      <div class="grid grid-cols-11 gap-1">
                          <div class="col-span-2"><a href="{{ route('gene-show', $item->gene->uuid) }}">{{ $item->gene->title }}</a></div>
                          <div class="col-span-7">{{ $disease->title }}<div class="text-xs">{{ $item->mondo_ref }}{{ $item->omim_ref }}</div></div>
                          <div class="col-span-2">{{ $item->submitter }}</div>
                      </div>
                  </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
</div>


@endsection
