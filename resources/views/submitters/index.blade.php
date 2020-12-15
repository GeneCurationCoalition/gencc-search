@extends('layouts.app')
@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Submitters</h1></div>
      <div class="col-span-2 pt-5">
                <div class="text-right mt-6"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html#submitter-index"><i class="fas fa-question-circle"></i> Help</a></div>

      </div>
  </div>
<div class="mt-2 mb-6">
  <p class="mb-2">The GenCC comprises organizations that currently provide online resources, as well diagnostic laboratories that have committed to sharing their internal curated gene-level knowledge.</p>
</div>
@endsection
@section('content')

  @include('partials.submitter.submitter-grid')



@endsection
