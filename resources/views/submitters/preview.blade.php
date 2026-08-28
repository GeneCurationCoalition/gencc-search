@extends('layouts.app')
@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Members</h1></div>
      <div class="col-span-2 pt-5">
                <div class="text-right mt-6"><a class="px-3" target="_blank" href="{{ route('faq') }}"><i class="fas fa-question-circle"></i> Help</a></div>

      </div>
  </div>
<div class="mt-2 mb-6">
  <p class="mb-2">The GenCC comprises organizations that currently provide online resources, as well diagnostic laboratories that have committed to sharing their internal curated gene-level knowledge.</p>
</div>
@endsection
@section('content')

  @php
    $variants = [
      '0' => 'Today: card grid',
      '1' => 'A: compact stripe row',
      '2' => 'B: aligned table',
      '3' => 'C: two-up condensed',
    ];
  @endphp
  <div class="mb-6 p-3 bg-yellow-100 border border-yellow-400 rounded">
    <p class="text-sm font-medium text-gray-700 mb-2">Layout preview (throwaway, issue #219)</p>
    <div class="flex flex-wrap">
      @foreach($variants as $key => $label)
        <a href="{{ route('members-preview', $key) }}"
           class="mr-2 mb-1 px-3 py-1 text-sm rounded border {{ $key === $variant ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-blue-700 border-gray-400 hover:bg-gray-50' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>
  </div>

  @include($variantPartial)

  {{ $submitters->links() }}

@endsection
