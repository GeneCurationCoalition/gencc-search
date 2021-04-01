@extends('layouts.app')

@section('content')

<div class=" flex bg-white">
  <!-- Static sidebar for desktop -->
  @include('partials.navs.dashboard-nav')
  <!-- Main column -->
  <div class="flex flex-col w-0 flex-1">
    <!-- Search header -->
    <main class="flex-1 z-0 focus:outline-none" tabindex="0">
      <!-- Page title & actions -->
      <div class="px-4 py-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <div class="flex-1 min-w-0">
          <h1 class="text-lg font-medium leading-6 text-gray-900 sm:truncate">
            {{ $file->submitter->title }}

          </h1>
          <div class="my-4">
            <div class="inline text-blue-700 text-center border-blue-500 border px-4 py-2"><a href="{{ route('manage-submitter-show', $file->submitter->curie) }}" class="text-blue-600">Submissions</a></div>
            <div class="inline text-white bg-blue-500 text-center border px-4 py-2"><a href="{{ route('manage-submitter-show-files', $file->submitter->curie) }}" class="text-white">Submissions Files</a></div>
            <div class="inline text-blue-700 text-center border-blue-500 border px-4 py-2"><a href="{{ route('manage-submitter-show-profile', $file->submitter->curie) }}" class="text-blue-600">Manage Info &amp; Manage Status</a></div>
          </div>

        </div>
      </div>

      <!-- Projects table (small breakpoint and up) -->
      <div class="mt-0 sm:block">
        <div class="align-middle inline-block min-w-full border-b border-gray-200">
          <h3 class="ml-8 pb-0 border-b">File Management </h3>
          @livewire('dashboard.submitter.submitter-file-manage', [$file])
        </div>
      </div>



    </main>
  </div>
</div>






@endsection
