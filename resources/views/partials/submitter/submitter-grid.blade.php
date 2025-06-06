<div class="mt-2 grid gap-5 max-w-lg mx-auto lg:grid-cols-2 xl:grid-cols-3 lg:max-w-none">
    @forelse ($submitters as $submitter)
      <div class="flex flex-col rounded-lg shadow-lg border border-gray-400 overflow-hidden">
        <div class="flex-shrink-0">
          <a href="{{ route('submitter-show', $submitter->uuid) }}" class="block">
          <img class="h-48 w-full object-cover" src="/brand/submitters/{{ $submitter->uuid }}.png" alt="">
          </a>
        </div>
        <div class="flex-1 bg-white pt-3 pb-3 px-6 flex flex-col justify-between">
          <div class="flex-1">
            <a href="{{ route('submitter-show', $submitter->uuid) }}" class="block hover:underline">
              <h3 class="mt-0 text-xl leading-7 font-semibold">
               {{ $submitter->title }}
              </h3>
              {{-- <p class="mt-3 text-base leading-6 text-gray-500">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto accusantium praesentium eius, ut atque fuga culpa, similique sequi cum eos quis dolorum.
              </p> --}}
              <a href="{{ route('submitter-show', $submitter->uuid) }}" class=" text-blue-700 text-xs hover:underline">
                @if($submitter->count_submissions != 0)
                  View data submissions and learn more
                @else
                  Learn more
                @endif
              <i class="far fa-arrow-alt-circle-right"></i></a>
            </a>
          </div>
        </div>
        @if($submitter->count_submissions != 0)
        <div class="flex-1 bg-white pb-3 px-6 flex flex-col justify-between  border-t">

          <p class="flex justify-between mt-2 text-sm leading-5 font-medium text-gray-500">
            <span>
              Submission Data Stats
            </span>
            <span>
              (Total {{ $submitter->curations_definitive + $submitter->curations_strong + $submitter->curations_moderate + $submitter->curations_supportive + $submitter->curations_limited + $submitter->curations_disputed +  $submitter->curations_refuted + $submitter->curations_animal + $submitter->curations_noknown }} Submissions)
            </span>
          </p>
          <div class="mt-1 mb-3 flex items-center -ml-4 -mr-4">
              <div class="grid grid-cols-9 gap-1 w-full">
                    <div class="col-span-3 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-3 gap-1/2'>
                            {!! $submitter->displayCurationCountPill($submitter->curations_definitive, "definitive", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_strong, "strong", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_moderate, "moderate", route('submitter-show', $submitter->uuid)) !!}
                        </div>
                    </div>
                    <div class="col-span-1 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-1 gap-1/2'>
                            {!! $submitter->displayCurationCountPill($submitter->curations_supportive, "supportive", route('submitter-show', $submitter->uuid)) !!}
                         </div>
                    </div>
                    <div class="col-span-5 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
                        <div class='grid grid-cols-5 gap-1/2'>
                            {!! $submitter->displayCurationCountPill($submitter->curations_limited, "limited", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_disputed, "disputed", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_refuted, "refuted", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_animal, "animal-model-only", route('submitter-show', $submitter->uuid)) !!}
                            {!! $submitter->displayCurationCountPill($submitter->curations_noknown, "no-known-disease-relationship", route('submitter-show', $submitter->uuid)) !!}
                        </div>
                    </div>
                </div>
          </div>
        </div>

          @else
            <p class="mb-8 text-sm leading-5 text-center font-medium text-gray-500">
                Submission Data Coming Soon
            </p>
          @endif
      </div>
    @empty
        Sorry, we don't seem to have anything...
    @endforelse
</div>