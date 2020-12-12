@extends('layouts.app')

@php
  $hide_modal = true;
@endphp

@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">Oops...</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10 text-center">
    <img src="/brand/errors/gencc-error-artwork.gif" class=" w-64 mx-auto" />
    <h2>An unitended 404 mutation occurred!</h2>
    <p>The information has moved or unavailable at this time. <br /><a class="text-blue-500 underline" href="{{ route('home') }}">Please click here to the home page.</a></p>
</div>

@endsection
