@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate"> Genes</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="gencc-help" href="https://thegencc.org/resources/help/#gene-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection
@section('content')
@livewire('genes.listing')

@endsection
