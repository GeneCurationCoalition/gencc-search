{{-- Variant C: two-up condensed cards. --}}
<div class="grid gap-4 lg:grid-cols-2">
  @forelse ($submitters as $submitter)
    @php
      $countSummary = $submitterCountSummaries->get($submitter->id);
      $hasCountSummary = $countSummary !== null;
      $displayCounts = $countSummary['displayCounts'] ?? [];
      $submitterSubmissionsCount = $countSummary['total'] ?? null;
      $memberUrl = route('member-show', $submitter->curie);
      $hasCounts = $submitter->allow_submissions && $hasCountSummary && $submitterSubmissionsCount > 0;
    @endphp
    <div class="rounded-lg border border-gray-400 bg-white p-3">
      <div class="flex items-center">
        <a href="{{ $memberUrl }}" class="block flex-shrink-0">
          <img class="w-32 max-w-full h-16 object-contain mr-3" src="{{ route('submitter-logo', $submitter->ident) }}" loading="lazy" alt="{{ $submitter->title }}">
        </a>
        <div class="flex-1">
          <a href="{{ $memberUrl }}" class="list-text-label list-link">
            {{ $submitter->title }}
            <div class="list-text-desc">
              @if($hasCounts)
                {{ number_format($submitterSubmissionsCount) }} submissions &middot; view data and learn more
              @elseif(!$submitter->allow_submissions)
                Member &middot; learn more
              @elseif(!$hasCountSummary)
                Submission counts unavailable &middot; learn more
              @else
                Submission Data Coming Soon &middot; learn more
              @endif
              <i class="far fa-arrow-alt-circle-right"></i>
            </div>
          </a>
        </div>
      </div>
      @if($hasCounts)
        <div class="mt-2">
          @include('partials.submitter.list-variants.pill-bar', ['displayCounts' => $displayCounts, 'memberUrl' => $memberUrl, 'submitter' => $submitter])
        </div>
      @endif
    </div>
  @empty
      Sorry, we don't seem to have anything...
  @endforelse
</div>
