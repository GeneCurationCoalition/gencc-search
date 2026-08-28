{{-- Shared classification pill bar, same grouping as the genes listing. --}}
<div class="grid grid-cols-9 gap-1">
  <div class="col-span-3 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
    <div class="grid grid-cols-3 gap-1">
      {!! $submitter->displayCurationCountPill($displayCounts['definitive'], "definitive", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['strong'], "strong", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['moderate'], "moderate", $memberUrl) !!}
    </div>
  </div>
  <div class="col-span-1 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
    <div class="grid grid-cols-1 gap-1">
      {!! $submitter->displayCurationCountPill($displayCounts['supportive'], "supportive", $memberUrl) !!}
    </div>
  </div>
  <div class="col-span-5 bg-gray-300 border-white-100 border-solid border-2 rounded-full py-1 px-1">
    <div class="grid grid-cols-5 gap-1">
      {!! $submitter->displayCurationCountPill($displayCounts['limited'], "limited", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['disputed'], "disputed", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['refuted'], "refuted", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['animal'], "animal-model-only", $memberUrl) !!}
      {!! $submitter->displayCurationCountPill($displayCounts['noknown'], "no-known", $memberUrl) !!}
    </div>
  </div>
</div>
