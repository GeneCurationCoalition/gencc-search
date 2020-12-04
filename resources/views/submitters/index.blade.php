@extends('layouts.app')
@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Submitters</h1></div>
      <div class="col-span-2 pt-5">
                <div class="text-right mt-6"><a class="px-3" target="gencc-help" href="https://thegencc.org/resources/help.html#submitter-index"><i class="fas fa-question-circle"></i> Help</a></div>

      </div>
  </div>
<div class="mt-2 mb-6">
  <p class="mb-2">The GenCC comprises organizations that currently provide online resources, as well diagnostic laboratories that have committed to sharing their internal curated gene-level knowledge.</p>
</div>
@endsection
@section('content')
<div class="mt-12 grid gap-5 max-w-lg mx-auto lg:grid-cols-2 xl:grid-cols-3 lg:max-w-none">
    @forelse ($submitters as $submitter)
      <div class="flex flex-col rounded-lg shadow-lg border border-gray-400 overflow-hidden">
        <div class="flex-shrink-0">
          <a href="{{ route('submitter-show', $submitter->uuid) }}" class="block">
          <img class="h-48 w-full object-cover" src="{{ $submitter->path_logo }}" alt="">
          </a>
        </div>
        <div class="flex-1 bg-white pt-3 pb-3 px-6 flex flex-col justify-between border-b">
          <div class="flex-1">
            <a href="{{ route('submitter-show', $submitter->uuid) }}" class="block hover:underline">
              <h3 class="mt-0 text-xl leading-7 font-semibold">
               {{ $submitter->title }}
              </h3>
              {{-- <p class="mt-3 text-base leading-6 text-gray-500">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto accusantium praesentium eius, ut atque fuga culpa, similique sequi cum eos quis dolorum.
              </p> --}}
              <a href="{{ route('submitter-show', $submitter->uuid) }}" class=" text-blue-600 text-xs hover:underline">View All Submissions <i class="far fa-arrow-alt-circle-right"></i></a>
            </a>
          </div>
        </div>

        <div class="flex-1 bg-white pb-3 px-6 flex flex-col justify-between">
            <p class="mt-2 text-sm leading-5 font-medium text-gray-500">
                Submission Stats
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
      </div>
    @empty
        Sorry, we don't seem to have anything...
    @endforelse
</div>




@endsection
