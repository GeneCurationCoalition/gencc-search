@extends('layouts.app')

@section('content')

<div class="h-screen flex overflow-hidden bg-white">
  <!-- Static sidebar for desktop -->
  @include('partials.navs.dashboard-nav')
  <!-- Main column -->
  <div class="flex flex-col w-0 flex-1 overflow-hidden">
    <!-- Search header -->
    <main class="flex-1 relative z-0 overflow-y-auto focus:outline-none" tabindex="0">
      <!-- Page title & actions -->
      <div class="border-b border-gray-200 px-4 py-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <div class="flex-1 min-w-0">
          <h1 class="text-lg font-medium leading-6 text-gray-900 sm:truncate">
            Manage {{ $submitter->title }}
          </h1>
        </div>
      </div>
      <!-- Projects table (small breakpoint and up) -->
      <div class="sm:block">
        <div class="align-middle inline-block min-w-full border-gray-200">
          <div class="px-8 py-4">
            <h3>Submission File Upload</h3>
          @livewire('dashboard.submission-upload', ['submitter' => $submitter])
            <div class="pl-3 mt-1 text-xs">Supported formats: XLS, XLSX</div>
          </div>
        </div>
      </div>
      <!-- Projects table (small breakpoint and up) -->
      <div class="mt-0 sm:block">
        <div class="align-middle inline-block min-w-full border-b border-gray-200">
          <div class="px-8 pb-4 italic">Note: Enabling/disabling files controls the files processed during the "Process Submissions" action.  <br />Note: Disabling a file doesn't remove submissions already uploaded.</div>
          @livewire('dashboard.submitter-file-list', ['submitter' => $submitter])
        </div>
      </div>
    </main>
  </div>
</div>






@endsection
