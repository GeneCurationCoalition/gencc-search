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
            {{ $submitter->title }}

          </h1>
          <div class="my-4">
            <div class="inline text-blue-700 text-center border-blue-500 border px-4 py-2"><a href="{{ route('manage-submitter-show', $submitter->curie) }}" class="text-blue-600">Submissions</a></div>
            <div class="inline text-blue-700 text-center border-blue-500 border px-4 py-2"><a href="{{ route('manage-submitter-show-files', $submitter->curie) }}" class="text-blue-600">Submissions Files</a></div>
            <div class="inline text-white bg-blue-500 text-center border px-4 py-2"><a href="{{ route('manage-submitter-show-profile', $submitter->curie) }}" class="text-white">Manage Info &amp; Manage Status</a></div>
          </div>

        </div>
      </div>


            <!-- Projects table (small breakpoint and up) -->
      <div class="sm:block" id="link_info">
        <div class="align-middle inline-block min-w-full border-gray-200">
          <div class="px-8 py-4">
            <h3>Manage Submitter Info</h3>

            @livewire('dashboard.submitter.manage-submitter-profile', ['submitter' => $submitter])

          </div>
        </div>
      </div>



            <!-- Projects table (small breakpoint and up) -->
      <div class="sm:block" id="link_status">
        <div class="align-middle inline-block min-w-full border-gray-200">
          <div class="px-8 py-4">
            <h3>Manage Status</h3>
            @livewire('dashboard.submitter.toggle-submitter-status', ['submitter' => $submitter])

          </div>
        </div>
      </div>
    </main>
  </div>
</div>






@endsection
