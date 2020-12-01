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
            {{ Auth::user()->name }}'s Dashboard
          </h1>
        </div>
      </div>
      @include('partials.dashboard.quick-stats')
      <!-- Projects table (small breakpoint and up) -->
      <div class="mt-8 sm:block">
        <div class="align-middle inline-block min-w-full border-b border-gray-200">
          <table class="min-w-full">
            <thead>
              <tr class="border-t border-gray-200">
                <th class="px-8 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Notifications
                </th>
                <th class="hidden md:table-cell px-6 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th class="pr-6 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">


              <tr>
                <td class="px-8 py-3 max-w-0 w-full whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                    <a href="#" class="truncate hover:text-gray-600">
                      Message of some sort...
                    </a>
                </td>
                <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                  March 17, 2020
                </td>
              </tr>

              <!-- More project rows... -->
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>






@endsection
