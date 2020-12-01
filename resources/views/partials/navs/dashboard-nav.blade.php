<div class="hidden lg:flex lg:flex-shrink-0">
    <div class="flex flex-col w-64 border-r border-gray-200 pb-4 bg-gray-100">
      <!-- Sidebar component, swap this element with another sidebar if you like -->
      <div class="h-0 flex-1 flex flex-col overflow-y-auto">
        <!-- User account dropdown -->
        <div class="px-3 mt-6 relative inline-block text-left">
          <!-- Dropdown menu toggle, controlling the show/hide state of dropdown menu. -->
          <div>
            <button type="button" class="group w-full rounded-md px-3.5 py-2 text-sm leading-5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-500 focus:outline-none focus:bg-gray-200 focus:border-blue-300 active:bg-gray-50 active:text-gray-800 transition ease-in-out duration-150" id="options-menu" aria-haspopup="true" aria-expanded="true">
              <div class="flex w-full justify-between items-center">
                <div class="flex min-w-0 items-center justify-between space-x-3">
                  <div class=" text-4xl pl-1"><i class="fas fa-user-circle"></i></div>
                  <div class="flex-1 min-w-0 text-left">
                    <div class=" text-sm">
                      <h2 class=" leading-5 text-sm font-medium truncate">{{ Auth::user()->name }}</h2>
                      <div class="text-xs font-light">
                        {{-- <i class="fas fa-cog"></i> --}}
                        Edit Preferences
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>
        <nav class="px-3 mt-6">
          <div class="space-y-1">
            <a href="{{ route('dashboard')}}" class="group flex items-center px-2 py-2 text-sm leading-5 font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition ease-in-out duration-150">
              <!-- Heroicon name: home -->
              <i class="fas fa-home mx-2"></i>
              Dashboard
            </a>

            <a href="#" class="group flex items-center px-2 py-2 text-sm leading-5 font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition ease-in-out duration-150">
              <!-- Heroicon name: view-list -->
              <i class="fas fa-thumbtack mx-2"></i>
              Followed Genes
            </a>

             <a href="#" class="group flex items-center px-2 py-2 text-sm leading-5 font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition ease-in-out duration-150">
              <!-- Heroicon name: view-list -->
              <i class="fas fa-thumbtack mx-2"></i>
              Followed Submitters
            </a>
{{--
            <a href="#" class="group flex items-center px-2 py-2 text-sm leading-5 font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition ease-in-out duration-150">
              <!-- Heroicon name: clock -->
              <i class="fas fa-cog mx-2"></i>
              My Settings
            </a> --}}

            <hr />
            <div class="pl-2 pt-3 text-gray-500 text-sm"> Administrator Options</div>
            <a href="{{ route('manage-submitters')}}" class="group flex items-top px-2 py-2 text-sm leading-5 font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition ease-in-out duration-150">
              <!-- Heroicon name: clock -->
              <i class="fas fa-tools mx-2 pt-1"></i>
              Manage Submitters &amp; Submissions
            </a>
          </div>

        </nav>
      </div>
    </div>
  </div>