{{-- Variant A: compact full-width stripe row per member. --}}
<div class="row-stripes border-t border-gray-300">
  @forelse ($submitters as $submitter)
    @php
      $countSummary = $submitterCountSummaries->get($submitter->id);
      $hasCountSummary = $countSummary !== null;
      $displayCounts = $countSummary['displayCounts'] ?? [];
      $submitterSubmissionsCount = $countSummary['total'] ?? null;
      $memberUrl = route('member-show', $submitter->curie);
      $hasCounts = $submitter->allow_submissions && $hasCountSummary && $submitterSubmissionsCount > 0;
    @endphp
    <div class="row-stripe grid grid-cols-12 gap-3 items-center border-b border-gray-300 py-2 px-2">
      <div class="col-span-3 sm:col-span-2">
        <a href="{{ $memberUrl }}" class="block">
          <img class="w-32 max-w-full h-16 object-contain" src="{{ route('submitter-logo', $submitter->ident) }}" loading="lazy" alt="{{ $submitter->title }}">
        </a>
      </div>
      <div class="col-span-9 sm:col-span-4">
        <a href="{{ $memberUrl }}" class="list-text-label list-link">
          {{ $submitter->title }}
          <div class="list-text-desc">
            @if($hasCounts)
              View data submissions and learn more
            @else
              Learn more
            @endif
            <i class="far fa-arrow-alt-circle-right"></i>
          </div>
        </a>
      </div>
      @if($hasCounts)
        <div class="col-span-12 sm:col-span-1 text-right">
          <a href="{{ $memberUrl }}" class="list-text-label list-link">
            {{ number_format($submitterSubmissionsCount) }}
            <div class="list-text-desc">submissions</div>
          </a>
        </div>
        <div class="col-span-12 sm:col-span-5 pr-1">
          @include('partials.submitter.list-variants.pill-bar', ['displayCounts' => $displayCounts, 'memberUrl' => $memberUrl, 'submitter' => $submitter])
        </div>
      @else
        <div class="col-span-12 sm:col-span-6 text-right pr-1">
          <span class="list-text-desc">
            @if(!$submitter->allow_submissions)
              Member
            @elseif(!$hasCountSummary)
              Submission counts unavailable
            @else
              Submission Data Coming Soon
            @endif
          </span>
        </div>
      @endif
    </div>
  @empty
      Sorry, we don't seem to have anything...
  @endforelse
</div>
