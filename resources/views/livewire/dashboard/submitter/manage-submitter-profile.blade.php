<div>

  <div class="grid grid-cols-12 mb-4 gap-0">
      @if (session()->has('message'))
        <div class="col-span-12 mb-4 bg-green-200" >
            <span class="m-2 inline-block text-green-700"> {{ session('message') }}</span>
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show', $submitter->curie) }}" class="text-white">Return to list</a></div>
        </div>
        @else
        <div class="col-span-12 mb-4 " >
            @if(isset($submitter->title))
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show', $submitter->curie) }}" class="text-white">Return to list</a></div>
            @endif
        </div>
        @endif
      </div>


    @if(isset($submitter->title))
      <form class="w-full"  wire:submit.prevent="submit">
    @else
      <form class="w-full"  wire:submit.prevent="create">
    @endif


      <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Title
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input  wire:model="title" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="">
                  @error('title') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Curie/UUID
                  </label>
                </div>
                <div class="md:w-2/6">
                  <input readonly wire:model="curie" class=" w-full py-2 px-4" id="inline-full-name" type="text" value="">
                  @error('curie') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="md:w-2/6 ml-3">
                  <input readonly wire:model="uuid" class=" w-full py-2 px-4" id="inline-full-name" type="text" value="">
                  @error('uuid') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Website
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input wire:model="website" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="">
                  @error('website') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Logo
                  </label>
                </div>
                <div class="md:w-4/6">
                  The file should be labeled as <strong>{{ $this->uuid }}.png</strong><br>
                  <small>The PNG file should be added to GitHub in /public/brand/submitters/{{ $this->uuid }}.png</small>
                </div>
                <div class="md:w-1/6  text-right">
                <img class="w-20 float-right border border-gray-700 bg-indigo-200" src="/brand/submitters/{{ $this->uuid }}.png" alt="">
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Descriptions text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea wire:model="text_descriptions" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500"></textarea>
                  @error('text_descriptions') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Contact Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea wire:model="text_contact" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500"></textarea>
                  @error('text_contact') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Assertions Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea wire:model="text_assertions" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500"></textarea>
                  @error('text_assertions') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Disclaimer Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea wire:model="text_disclaimer" class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500"></textarea>
                  @error('text_disclaimer') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Submissions Are Downloadable
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input wire:model="downloadable" class="w-100 bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="">
                  @error('downloadable') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                  <div class="text-sm text-gray-600 ml-5 mt-1">1= Downloadable | 0= NOT Downloadable</div>
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                </div>
                <div class="md:w-5/6">
                  @if(isset($submitter->title))
                      <button class="bg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Save</button><span class="m-2 inline-block text-green-700"> {{ session('message') }}</span>
                  @else
                      <button class="bg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Create</button><span class="m-2 inline-block text-green-700"> {{ session('message') }}</span>
                  @endif
                </div>
              </div>



            </form>
</div>
