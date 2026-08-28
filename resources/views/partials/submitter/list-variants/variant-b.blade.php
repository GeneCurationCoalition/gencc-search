{{-- Variant B: aligned table, one fixed column per classification. --}}
@php
  $columns = [
    ['type' => 'definitive', 'abbr' => 'DEF', 'title' => 'Definitive'],
    ['type' => 'strong', 'abbr' => 'STR', 'title' => 'Strong'],
    ['type' => 'moderate', 'abbr' => 'MOD', 'title' => 'Moderate'],
    ['type' => 'supportive', 'abbr' => 'SUP', 'title' => 'Supportive'],
    ['type' => 'limited', 'abbr' => 'LIM', 'title' => 'Limited'],
    ['type' => 'disputed', 'abbr' => 'DIS', 'title' => 'Disputed Evidence'],
    ['type' => 'refuted', 'abbr' => 'REF', 'title' => 'Refuted Evidence'],
    ['type' => 'animal', 'abbr' => 'ANI', 'title' => 'Animal Model Only'],
    ['type' => 'noknown', 'abbr' => 'NKD', 'title' => 'No Known Disease Relationship'],
  ];
  $pillTypes = [
    'definitive' => 'definitive',
    'strong' => 'strong',
    'moderate' => 'moderate',
    'supportive' => 'supportive',
    'limited' => 'limited',
    'disputed' => 'disputed',
    'refuted' => 'refuted',
    'animal' => 'animal-model-only',
    'noknown' => 'no-known',
  ];
@endphp
<div class="overflow-x-auto">
  <table class="w-full min-w-full">
    <thead>
      <tr class="border-b border-gray-400">
        <th class="text-left list-text-desc font-medium pb-1 pl-2">Member</th>
        <th class="text-right list-text-desc font-medium pb-1 px-2">Total</th>
        @foreach($columns as $column)
          <th class="list-text-desc font-medium pb-1 px-1 text-center" title="{{ $column['title'] }}">{{ $column['abbr'] }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody class="row-stripes">
      @forelse ($submitters as $submitter)
        @php
          $countSummary = $submitterCountSummaries->get($submitter->id);
          $hasCountSummary = $countSummary !== null;
          $displayCounts = $countSummary['displayCounts'] ?? [];
          $submitterSubmissionsCount = $countSummary['total'] ?? null;
          $memberUrl = route('member-show', $submitter->curie);
          $hasCounts = $submitter->allow_submissions && $hasCountSummary && $submitterSubmissionsCount > 0;
        @endphp
        <tr class="row-stripe border-b border-gray-300">
          <td class="py-1 pl-2">
            <a href="{{ $memberUrl }}" class="flex items-center list-text-label list-link">
              <img class="w-24 h-12 object-contain mr-3 flex-shrink-0" src="{{ route('submitter-logo', $submitter->ident) }}" loading="lazy" alt="{{ $submitter->title }}">
              <span class="whitespace-no-wrap">
                {{ $submitter->title }}
                <div class="list-text-desc">Learn more <i class="far fa-arrow-alt-circle-right"></i></div>
              </span>
            </a>
          </td>
          @if($hasCounts)
            <td class="py-1 px-2 text-right list-text-label whitespace-no-wrap">
              <a href="{{ $memberUrl }}" class="list-text-label list-link">{{ number_format($submitterSubmissionsCount) }}</a>
            </td>
            @foreach($columns as $column)
              <td class="py-1 px-1 align-middle">
                {!! $submitter->displayCurationCountPill($displayCounts[$column['type']], $pillTypes[$column['type']], $memberUrl) !!}
              </td>
            @endforeach
          @else
            <td class="py-1 px-2 text-right list-text-desc">&mdash;</td>
            <td class="py-1 px-1 list-text-desc whitespace-no-wrap" colspan="{{ count($columns) }}">
              @if(!$submitter->allow_submissions)
                Member
              @elseif(!$hasCountSummary)
                Submission counts unavailable
              @else
                Submission Data Coming Soon
              @endif
            </td>
          @endif
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($columns) + 2 }}">Sorry, we don't seem to have anything...</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
