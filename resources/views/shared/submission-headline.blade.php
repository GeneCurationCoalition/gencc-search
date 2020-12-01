  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-9 text-white mb-2">
        <h1 class="mb-0 pb-0 truncate">Submission Details</h1>
        <div class="text-sm text-blue-400">GenCC Ref: {{ $submission->uuid }}</a></div>
      </div>
      <div class="col-span-3 pt-5">
        <div class="text-right text-md"><a href="{{ route('submitter-show', $submission->submitter->uuid) }}" class="rounded-full py-1 px-5 text-center border-2 border-solid border-blue-900 text-blue-600 bg-white whitespace-no-wrap hover:bg-blue-200"><i class="far fa-arrow-alt-circle-left"></i> Return to submitter list</a></div>
       <div class="text-right mt-6"><a class="px-3" target="gencc-help" href="https://thegencc.org/resources/help/#submission-show"><i class="fas fa-question-circle"></i> Help</a></div>

      </div>
  </div>