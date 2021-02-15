<div>

    <form class="w-full">

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Title
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="{{ $submitter->title }}">
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Curie
                  </label>
                </div>
                <div class="md:w-2/6">
                  <input class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="{{ $submitter->curie }}">
                </div>
                <div class="md:w-2/6 ml-3">
                  {{ $submitter->uuid }}
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Website
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="{{ $submitter->website }}">
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Logo
                  </label>
                </div>
                <div class="md:w-4/6">
                  The file should be labeled as <strong>{{ $submitter->uuid }}.png</strong><br>
                  <small>The PNG file should be added to GitHub in /public/brand/submitters/{{ $submitter->uuid }}.png</small>
                </div>
                <div class="md:w-1/6  text-right">
                <img class="w-20 float-right border border-gray-700 bg-indigo-200" src="/brand/submitters/{{ $submitter->uuid }}.png" alt="">
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Descriptions text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500">{{ $submitter->text_descriptions }}</textarea>
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Contact Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500">{{ $submitter->text_contact }}</textarea>
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Assertions Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500">{{ $submitter->text_assertions }}</textarea>
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Disclaimer Text
                  </label>
                </div>
                <div class="md:w-5/6">
                  <textarea class="bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500">{{ $submitter->text_disclaimer }}</textarea>
                </div>
              </div>

              <div class="md:flex md:items-center mb-6">
                <div class="md:w-1/6">
                  <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4" for="inline-full-name">
                    Submissions Are Downloadable
                  </label>
                </div>
                <div class="md:w-5/6">
                  <input class="w-100 bg-gray-200 appearance-none border-2 border-gray-200 rounded w-full py-2 px-4 text-gray-700 leading-tight focus:outline-none focus:bg-white focus:border-purple-500" id="inline-full-name" type="text" value="{{ $submitter->downloadable }}">
                </div>
              </div>

            </form>
</div>
