@extends('layouts.app')

@section('content')
<h1>Disease  <span class=" text-xl text-gray-500 ">[Showing {{  $diseases->total()  }}]</span></h1>
<hr />
@foreach ($diseases as $item)
    <div class="border-t border-t-gray-200 border-t-solid pt-2 mt-2">
        <div class="grid grid-cols-12 gap-1">
            <div class="col-span-4 normal-case  pr-6"><a href="{{ route('disease-show', $item->curie) }}">{{ $item->title }}</a></div>
            <div class="col-span-4 text-gray-500"><a href="{{ route('disease-show', $item->curie) }}">{{ $item->count_submissions}} @if($item->count_submissions > 1) submissions @else submission @endif related to {{ $item->count_unique_genes}} unique genes</a></div>
            <div class="col-span-4">
                <div class='grid grid-cols-9 gap-2'>
                    {!! $item->displayCurationCountPill($item->curations_definitive, "definitive", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_strong, "strong", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_moderate, "moderate", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_limited, "limited", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_disputed, "disputed", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_refuted, "refuted", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_animal, "animal", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_noknown, "noknown", route('gene-show', $item->curie)) !!}
                    {!! $item->displayCurationCountPill($item->curations_nul, "nul", route('gene-show', $item->curie)) !!}
                </div>
            </div>
        </div>
    </div>
@endforeach

{{ $diseases->links() }}

@endsection
