@extends('layouts.app')

@section('headline')
  @include("shared/submission-headline")
@endsection
@section('content')
<div class="grid grid-cols-12 gap-0">
    <div class="col-span-12">
      {{-- Previous Version Warning Banner - shown when viewing a previous version with a current version available --}}
      @if($isPreviousVersion ?? false)
      <div class="p-6 mb-4" style="background-color: #FEF3C7; border: 2px solid #92400E;">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6" style="color: #92400E;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-base font-semibold" style="color: #000000;">Previous Version</h3>
            <p class="mt-1 text-sm" style="color: #000000;">
              You are looking at a previous version of this record ({{ $submission->display_id }}).
              @if(isset($currentVersion) && $currentVersion && $currentVersion->status === 'published')
                <a href="{{ route('submission-show', ['id' => $currentVersion->display_id]) }}" class="font-semibold underline" style="color: #0000EE;">Please see the current version</a>.
              @endif
            </p>
          </div>
        </div>
      </div>
      @endif

      {{-- Unpublished Submission Banner - shown when submission is explicitly unpublished --}}
      @if($isExplicitlyUnpublished ?? false)
      <div class="p-6 mb-4" style="background-color: #FEE2E2; border: 2px solid #991B1B;">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6" style="color: #991B1B;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-base font-semibold" style="color: #000000;">Unpublished Submission</h3>
            <p class="mt-1 text-sm" style="color: #000000;">
              This submission ({{ $submission->display_id }}) was unpublished on {{ Carbon\Carbon::parse($unpublishedDate)->format('m/d/Y') }} by {{ $submission->submitter->title }}.
            </p>
          </div>
        </div>
      </div>
      @endif

      {{-- Show details only if not hidden --}}
      @if(!($hideDetails ?? false))
      <div class="grid grid-cols-12 gap-0 mt-4">


        <div class="col-span-2 pt-3 text-right pr-3">Submitter:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal font-bold"><a class="underline" href="{{ route('member-show', $submission->submitter->uuid) }}">{{ $submission->submitter->title }}</a></div>
          {{-- <div class="text-xs">{{ $submission->submitter->curie }}</div> --}}
        </div>

        <hr class="col-span-12 my-4" />

        <div class="col-span-2 pt-3 text-right pr-3">Accession:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          <div class="font-normal">{{ $submission->display_id }}</div>
        </div>

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
              <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease->curie, $submission->disease->curie) !!}{!! $submission->displayDeprecationIndicator($submission->disease) !!}</div>
            </div>
            @if($submission->disease_original && $submission->disease_id != $submission->disease_original_id)
            <div class="mb-2">
              <div class="text-xs text-gray-500 font-semibold">Submitted as:</div>
              <div class="font-normal">{{ $submission->disease_original->title }}</div>
                <div class="text-xs">{!! $submission->displayLinkToDisease($submission->disease_original->curie, $submission->disease_original->curie) !!}{!! $submission->displayDeprecationIndicator($submission->disease_original) !!}</div>
            </div>
            @endif
        </div>

        <div class="col-span-2 pt-3 text-right pr-3">Mode Of Inheritance:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
          @if($submission->inheritance)
          <div class="font-normal">{{ $submission->inheritance->title }}</div>
          <div class="text-xs">{!! $submission->displayLinkToMoi($submission->inheritance->curie, $submission->inheritance->curie) !!}</div>
          @else
          <div class="font-normal text-gray-500">N/A</div>
          @endif
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


          @if ($submission->pubmeds->count() > 0)
        <div class="col-span-2 pt-3 text-right pr-3">PubMed IDs:</div>
        <div class="col-span-10 py-1 my-2 border-l-8 pl-3">
            @php
                $pmids = $submission->pubmeds->pluck('pmid')->sort(function ($a, $b) {
                    return intval($a) - intval($b);
                })->values()->all();
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
      @endif
    </div>
</div>


@endsection
