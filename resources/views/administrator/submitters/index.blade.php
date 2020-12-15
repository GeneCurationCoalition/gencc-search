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
            Manage Submitters
          </h1>
        </div>
      </div>
      <div class=" px-4 py-4 sm:flex sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <div class="flex-1 min-w-0">

            @livewire('dashboard.process-submissions')
        </div>
      </div>
      <!-- Projects table (small breakpoint and up) -->
      <div class="mt-0 sm:block">
        <div class="align-middle inline-block min-w-full border-b border-gray-200">
          <table class="min-w-full">
            <thead>
              <tr class="border-t border-gray-200">
                <th class="border-b border-gray-200 bg-gray-50 "></th>
                <th class="pr-8 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Submitter
                </th>
                <th class="hidden md:table-cell px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Files
                </th>
                <th class="hidden md:table-cell px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Identifer
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">

              @foreach ($submitters as $submitter)

              <tr>
                <td class="px-2 py-3 max-w-0 whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                  @if($submitter->status == 1)
                    <span class=" bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full">S</span>
                  @else
                    <span class=" bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full">H</span>
                  @endif
                      {{-- {{ $submitter->status }} --}}
                </td>
                <td class="pr-8 py-3 max-w-0 w-full whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                    <a href="{{ route('manage-submitters-show', $submitter->curie) }}" class="truncate text-blue-700 hover:text-gray-600">
                      {{ $submitter->title }}
                    </a>
                </td>
                <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-left">
                  <span class=" bg-green-800 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full">{{ $submitter->submission_files->where('status', 1)->count() }} active</span> <span class=" bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full">{{ $submitter->submission_files->where('status', 0)->count() }} disabled </span>
                </td>
                <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-left">
                  {{ $submitter->curie }}
                </td>
              </tr>

              @endforeach
              <!-- More project rows... -->
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>






@endsection
