@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate"> Conflict Viewer</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="_blank" href="{{ route('faq') }}#website-pages-faq"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection
@section('content')
<p class="mb-4 text-gray-600">
    Each row below is a gene, disease and mode-of-inheritance assertion where at least one submitter reported
    Definitive, Strong or Moderate and at least one reported Limited, Disputed, Refuted or No Known Disease
    Relationship. Supportive and Animal Model Only submissions are excluded from this conflict logic. Only the
    current, published version of each submission is counted.
</p>

@livewire('conflict-viewer.listing')

@endsection
