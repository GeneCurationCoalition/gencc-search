@extends('layouts.app')

@section('headline')
  @include("shared/submission-headline")
@endsection
@section('content')
<div class="grid grid-cols-12 mt-4 gap-0">
    <div class="col-span-12">
      <div class="grid grid-cols-12 gap-0">


        <div class="col-span-2 pt-3 text-right pr-3">Submitter:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal font-bold"><a class="underline" href="{{ route('member-show', $submission->submitter->uuid) }}">{{ $submission->submitter->title }}</a></div>
          {{-- <div class="text-xs">{{ $submission->submitter->curie }}</div> --}}
        </div>

        {{-- <div class="col-span-2 pt-3 text-right pr-3">GenCC Submission ID:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal font-bold">{{ $submission->uuid }}</a></div>
        </div> --}}

        <hr class="col-span-12 my-4" />

        <div class="col-span-2 pt-3 text-right pr-3">Classification:</div>
        <div class="col-span-4 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal ">
            {!!  $submission->displayCurationLabelPill($submission->classification) !!}</div>
          <div class="text-xs">{{ $submission->classification->curie }}</div>
        </div>
        <div class="col-span-12"></div>

        <div class="col-span-2 pt-3 text-right pr-3">Gene:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          @if($submission->gene)
          <div class="font-normal"><a class="underline" href="{{ route('gene-show', $submission->gene->uuid) }}">{{ $submission->gene->title }}</a></div>
          <div class="text-xs">{!! $submission->displayLinkToHgnc($submission->gene->curie, $submission->gene->curie) !!}</div>
          @else
            <div class="font-normal">N/A</div>
          @endif
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Disease:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
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

        <div class="col-span-2 pt-3 text-right pr-3">Mode Of Inheritance:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ $submission->inheritance->title }}</div>
          <div class="text-xs">{!! $submission->displayLinkToMoi($submission->inheritance->curie, $submission->inheritance->curie) !!}</div>
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Evaluated Date:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ Carbon\Carbon::parse($submission->submitted_as_date)->format('m/d/Y') }}</div>
        </div>

        @if (strlen($submission->submitted_as_notes)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Evidence/Notes:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">

            <div class="font-normal prose prose-sm max-w-none">{!! $submission->renderMarkdown($submission->submitted_as_notes) !!}</div>

        </div>
        @endif


          @if (strlen($submission->submitted_as_pmids)>4)
        <div class="col-span-2 pt-3 text-right pr-3">PubMed IDs:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            @php
                $pmids = preg_split('/\D+/', $submission->submitted_as_pmids, -1, PREG_SPLIT_NO_EMPTY);
                $pubmedArticles = $submission->getPubmedArticles();

                // Create a map of PMIDs that have metadata
                $pmidsWithMetadata = [];
                if ($pubmedArticles) {
                    foreach ($pubmedArticles as $article) {
                        $pmidsWithMetadata[$article['pmid']] = $article;
                    }
                }

                $totalPmids = count($pmids);
                $showLimit = 5;
                $hasMore = $totalPmids > $showLimit;
            @endphp

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PMID</th>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pmids as $index => $pmid)
                            @php
                                $pmid = trim($pmid);
                                $hasMetadata = isset($pmidsWithMetadata[$pmid]);
                                $article = $hasMetadata ? $pmidsWithMetadata[$pmid] : null;
                                $isHidden = $hasMore && $index >= $showLimit;
                            @endphp
                            <tr class="hover:bg-gray-50 pubmed-row @if($isHidden) pubmed-extra hidden @endif">
                                <td class="px-3 py-3 whitespace-nowrap text-sm">
                                    <a href="https://pubmed.ncbi.nlm.nih.gov/{{ $pmid }}/" target="_blank" class="text-blue-700 underline font-semibold hover:text-blue-900">{{ $pmid }}</a>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">
                                    @if($hasMetadata && !empty($article['firstAuthor']))
                                        {{ $article['firstAuthor'] }} et al.
                                    @else
                                        <span class="text-gray-400 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">
                                    @if($hasMetadata && !empty($article['year']))
                                        {{ $article['year'] }}
                                    @else
                                        <span class="text-gray-400 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">
                                    @if($hasMetadata && !empty($article['title']))
                                        {{ $article['title'] }}
                                    @else
                                        <span class="text-gray-400 italic text-xs">Metadata needs to be refreshed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($hasMore)
                <div class="mt-3 text-center">
                    <button
                        onclick="togglePubmedRows(this)"
                        class="text-blue-700 hover:text-blue-900 text-sm font-medium focus:outline-none"
                        data-total="{{ $totalPmids }}"
                        data-shown="{{ $showLimit }}"
                    >
                        <span class="show-more-text">Show {{ $totalPmids - $showLimit }} more PubMed {{ $totalPmids - $showLimit === 1 ? 'article' : 'articles' }} <i class="fas fa-chevron-down ml-1"></i></span>
                        <span class="show-less-text hidden">Show less <i class="fas fa-chevron-up ml-1"></i></span>
                    </button>
                </div>

                <script>
                    function togglePubmedRows(button) {
                        const extraRows = document.querySelectorAll('.pubmed-extra');
                        const showMoreText = button.querySelector('.show-more-text');
                        const showLessText = button.querySelector('.show-less-text');
                        const isExpanded = !extraRows[0].classList.contains('hidden');

                        extraRows.forEach(row => {
                            if (isExpanded) {
                                row.classList.add('hidden');
                            } else {
                                row.classList.remove('hidden');
                            }
                        });

                        showMoreText.classList.toggle('hidden');
                        showLessText.classList.toggle('hidden');
                    }
                </script>
            @endif

        </div>
         @endif

          @if(strlen($submission->submitted_as_public_report_url)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Public Report:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="font-normal"><a class="underline"  id='click-exit-public-report' target="_blank" href="{{  $submission->submitted_as_public_report_url }}">Click here to view the public report <i class="fas fa-external-link-alt"></i></a></div>
            <div class="text-xs"><a class="" id='click-exit-public-report'  target="_blank" href="{{  $submission->submitted_as_public_report_url }}">{{ $submission->submitted_as_public_report_url }} <i class="fas fa-external-link-alt"></i></a></div>


        </div>
         @endif

          @if(strlen($submission->submitted_as_assertion_criteria_url)>2)
        <div class="col-span-2 pt-3 text-right pr-3">Assertion Criteria:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">

             @if (strpos(strtoupper($submission->submitted_as_assertion_criteria_url), 'HTTP') !== false)
              <div class="font-normal"><a class="underline" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">Click here to view assertion criteria <i class="fas fa-external-link-alt"></i></a></div>
              <div class="text-xs"><a class="" id='click-exit-assertion-criteria'  target="_blank" href="{{ $submission->submitted_as_assertion_criteria_url }}">{{ $submission->submitted_as_assertion_criteria_url }} <i class="fas fa-external-link-alt"></i></a></div>
            @elseif(preg_match('/PMID:.*?(\d+)/', $submission->submitted_as_assertion_criteria_url, $matches))
              <div class="font-normal"><a class="underline" id='click-exit-assertion-criteria'  target="_blank" href="https://pubmed.ncbi.nlm.nih.gov/{{ $matches[1] }}/">Click here to view assertion criteria <i class="fas fa-external-link-alt"></i></a></div>
              <div class="text-xs"><a class="" id='click-exit-assertion-criteria'  target="_blank" href="https://pubmed.ncbi.nlm.nih.gov/{{ $matches[1] }}/">PMID: {{ $matches[1] }} <i class="fas fa-external-link-alt"></i></a></div>
            @elseif($submission->submitted_as_assertion_criteria_url)
              <div class="font-normal">{{ $submission->submitted_as_assertion_criteria_url }}</div>
            @endif


        </div>
         @endif

        {{-- <div class="col-span-2 pt-3 text-right pr-3 pb-3">Submission ID from Submitter:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="">{{ $submission->submitted_as_submission_id }}</div>

        </div> --}}
        <div class="col-span-2 pt-3 text-right pr-3">Submitted Date:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            <div class="">@if($submission->submitted_run_date) {{ Carbon\Carbon::parse($submission->submitted_run_date)->format('m/d/Y') }} @else N/A @endif</div>

        </div>

      </div>
    </div>
</div>


@endsection
