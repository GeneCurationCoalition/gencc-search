<div>
    <div class="grid grid-cols-12 mt-4 gap-0">
    <div class="col-span-12">
      <div class="grid grid-cols-12 gap-0">


        @if (session()->has('message'))
        <div class="col-span-12 ml-8 mb-4 bg-green-200" >
            <span class="m-2 inline-block text-green-700"> {{ session('message') }}</span>
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show', $submission->submitter->curie) }}" class="text-white">Return to list</a></div>
        </div>
        @else
        <div class="col-span-12 ml-8 mb-4 " >
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show', $submission->submitter->curie) }}" class="text-white">Return to list</a></div>
        </div>
        @endif


        <div class="col-span-3 pt-3 text-right pr-3">UUID:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal ">{{ $submission->uuid }}</div>
          <div class="text-xs">ID: {{ $submission->id }}</div>
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Submitter:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal ">{{ $submission->submitter->title }}</div>
          <div class="text-xs">{{ $submission->submitter->curie }}</div>
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Classification:</div>
        <div class="col-span-4 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal ">
            {!!  $submission->displayCurationLabelPill($submission->classification) !!}</div>
          <div class="text-xs">{{ $submission->classification->curie }}</div>
        </div>
        <div class="col-span-12"></div>

        <div class="col-span-3 pt-3 text-right pr-3">Gene:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          @if($submission->gene)
          <div class="font-normal"><a class="underline" href="{{ route('gene-show', $submission->gene->uuid) }}">{{ $submission->gene->title }}</a></div>
          <div class="text-xs">{!! $submission->displayLinkToHgnc($submission->gene->curie, $submission->gene->curie) !!}</div>
          @else
            <div class="font-normal">N/A</div>
          @endif
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Disease:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          <div class="mb-2">
              <div class="font-normal">{{ $submission->disease->title }}</div>
              <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease->curie, $submission->disease->curie) !!}</div>
            </div>
            @if($submission->disease->id != $submission->disease_original->id)
            <div class="mb-2">
              <div class="font-normal">{{ $submission->disease_original->title }}</div>
                <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease_original->curie, $submission->disease_original->curie) !!}</div>
            </div>
            @endif
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Mode Of Inheritance:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ $submission->inheritance->title }}</div>
          <div class="text-xs">{!! $submission->displayLinkToMoi($submission->inheritance->curie, $submission->inheritance->curie) !!}</div>
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Evaluated Date:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ Carbon\Carbon::parse($submission->submitted_as_date)->format('m/d/Y') }}</div>
        </div>

        @if (strlen($submission->submitted_as_notes)>2)
        <div class="col-span-3 pt-3 text-right pr-3">Evidence/Notes:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">

            <div class="font-normal">{{ $submission->submitted_as_notes }}</div>

        </div>
        @endif


          @if (strlen($submission->submitted_as_pmids)>2)
        <div class="col-span-3 pt-3 text-right pr-3">PubMed IDs:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal">{{ $submission->submitted_as_pmids }}</div>

        </div>
         @endif

          @if(strlen($submission->submitted_as_public_report_url)>2)
        <div class="col-span-3 pt-3 text-right pr-3">Public Report:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal"><a class="underline"  id='click-exit-public-report' target="_blank" href="{{  $submission->submitted_as_public_report_url }}">Click here to view the public report <i class="fas fa-external-link-alt"></i></a></div>
            <div class="text-xs"><a class="" id='click-exit-public-report'  target="_blank" href="{{  $submission->submitted_as_public_report_url }}">{{ $submission->submitted_as_public_report_url }} <i class="fas fa-external-link-alt"></i></a></div>


        </div>
         @endif

          @if(strlen($submission->submitted_as_assertion_criteria_url)>2)
        <div class="col-span-3 pt-3 text-right pr-3">Assertion Criteria:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal"><a class="underline" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">Click here to view assertion criteria <i class="fas fa-external-link-alt"></i></a></div>
            <div class="text-xs"><a class="" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">{{ $submission->submitted_as_assertion_criteria_url }} <i class="fas fa-external-link-alt"></i></a></div>


        </div>
         @endif

        {{-- <div class="col-span-3 pt-3 text-right pr-3 pb-3">Submission ID from Submitter:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="">{{ $submission->submitted_as_submission_id }}</div>

        </div> --}}
        <div class="col-span-3 pt-3 text-right pr-3">Submitter Submitted Date:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="">@if($submission->submitted_run_date) {{ Carbon\Carbon::parse($submission->submitted_run_date)->format('m/d/Y') }} @else N/A @endif</div>

        </div>


        <div class="col-span-3 pt-3 text-right pr-3">Submission File Name:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal">{{ $submission->from_submission_file_name ?? "--" }}</div>

        </div>

        <div class="col-span-3 pt-3 text-right pr-3">Submission File ID:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal">{{ $submission->from_submission_file_id ?? "--" }}</div>

        </div>

        <div class="col-span-3 pt-3 text-right pr-3">DB Record Created:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="">@if($submission->created_at) {{ $submission->created_at }} @else N/A @endif</div>
        </div>

        <div class="col-span-3 pt-3 text-right pr-3">DB Record Updated:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            <div class="">@if($submission->updated_at) {{ $submission->updated_at }} @else N/A @endif</div>
        </div>


        <div class="col-span-3 pt-3 text-right pr-3">Status:</div>
        <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
            @if($submission->status == 1)
                <button wire:click="disable('{{ $submission->uuid }}')" class=" bg-green-800 hover:bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Enabled</button>
            @elseif($submission->status == 0)
                <button wire:click="archive('{{ $submission->uuid }}')" class=" bg-red-800 hover:bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Disabled</button>
            @else
                <button wire:click="enable('{{ $submission->uuid }}')" class="bg-gray-500 hover:bg-gray-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Archived</button>
            @endif

        </div>


        <div class="col-span-3 pt-4 text-right pr-3">Private Notes:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                <form wire:submit.prevent="save">
                    <textarea wire:model.defer="submission.private_notes" placeholder="Submission notes" class="w-full border border-gray-400 p-2">{{ $submission->private_notes }}</textarea>
                    <button class="bg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Save Notes

                        @if (session()->has('note_button'))
                            {{ session('note_button') }}
                        @endif
                    </button>

                            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show', $submission->submitter->curie) }}" class="text-white">Return to list</a></div>
                </form>
            </div>



        </div>
        </div>
        </div>
</div>
