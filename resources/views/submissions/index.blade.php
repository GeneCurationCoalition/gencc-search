@extends('layouts.app')

@section('content')
<h1>Submissions Index</h1>
<hr />
@foreach ($submissions as $item)
    <div class="border-t border-t-gray-200 border-t-solid pt-2 mt-2">
        <div class="grid grid-cols-6 gap-1">
            <div class=""><a href="{{ $item->id }}"><i class="fas fa-dna"></i> {{ $item->gene_symbol }}</a></div>
            <div class="">{{ $item->submitted_as_disease_name }}</div>
            <div class="">{{ $item->submitter }}</div>
            <div class="">{{ $item->mondo_ref }}</div>
            <div class="">{{ $item->omim_ref }}</div>
            <div class="">{!! $item->displayCurationLabelPill($item->classification) !!}</div>
        </div>
    </div>
@endforeach

{{ $submissions->links() }}

@endsection
