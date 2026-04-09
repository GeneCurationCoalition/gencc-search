@extends('layouts.app')


@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Data</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" href="{{ route('faq') }}"><i class="fas
  fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10">
  <div class="mb-6">
    <p class="mb-3">Member groups submit assertions about gene-disease relationships. Each entry will be an assertion for a gene, disease, and a mode of inheritance with a link to evidence supporting that assertion. Member groups have submitted data and will continue to add to the data set over time. Due to licensing restrictions, a GenCC download does not include OMIM data. OMIM data can be accessed and downloaded through <a id='click-exit-omim' href="https://www.omim.org/" class="underline" target="_blank">https://www.omim.org/</a></p>
  </div>

  <div class="grid lg:grid-cols-2 gap-4 lg:gap-20 xl:gap-40 mb-6">
    {{-- New Format Section --}}
    <div>
      <h2 class="flex items-center">
        <span class="bg-green-100 text-green-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">RECOMMENDED</span>
        New Format
      </h2>
      <p class="mb-3 text-sm text-gray-700">
        The new format uses <strong>SGC ID</strong> and <strong>version number</strong> as the first two columns to uniquely identify each submission. This format supports versioning and provides a stable identifier for each submission record.
      </p>
      <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
        <p class="text-sm text-green-800 mb-2"><strong>First columns:</strong></p>
        <code class="text-xs bg-green-100 px-2 py-1 rounded">sgc_id, version_number, gene_curie, ...</code>
        <p class="text-xs text-green-700 mt-2">Example: <code>SGC-107616, 2, HGNC:10896, ...</code></p>
      </div>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <a id="download-submissions-export-xlsx-new" href="{{ route('submissions-export-xlsx') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
        <a id="download-submissions-export-tsv-new" href="{{ route('submissions-export-tsv') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (tsv)</a>
        <a id="download-submissions-export-csv-new" href="{{ route('submissions-export-csv') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (csv)</a>
      </div>
    </div>

    {{-- Legacy Format Section --}}
    <div>
      <h2 class="flex items-center">
        <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">DEPRECATED</span>
        Legacy Format
      </h2>
      {{-- Deprecation Warning --}}
      <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-amber-500"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm text-amber-700">
              <strong>Deprecation Notice:</strong> The legacy format will be removed and unavailable after <strong>September 30, 2026</strong>. Please migrate to the new format before this date.
            </p>
          </div>
        </div>
      </div>
      <p class="mb-3 text-sm text-gray-700">
        The legacy format uses a single <strong>UUID</strong> column that combines submitter, gene, disease, inheritance, and classification identifiers into one composite key.
      </p>
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
        <p class="text-sm text-gray-800 mb-2"><strong>First column:</strong></p>
        <code class="text-xs bg-gray-100 px-2 py-1 rounded">uuid, gene_curie, ...</code>
        <p class="text-xs text-gray-700 mt-2">Example: <code>GENCC_000101-HGNC_10896-OMIM_182212-HP_0000006-GENCC_100001, ...</code></p>
      </div>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <a id="download-submissions-export-xlsx" href="{{ route('submissions-export-xlsx') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
        <a id="download-submissions-export-tsv" href="{{ route('submissions-export-tsv') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (tsv)</a>
        <a id="download-submissions-export-csv" href="{{ route('submissions-export-csv') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (csv)</a>
      </div>
    </div>
  </div>

  {{-- API Access Section --}}
  <div class="grid lg:grid-cols-2 gap-4 lg:gap-20 xl:gap-40 mb-6">
    <div>
      <h2>API Access</h2>
      <p class="mb-4">Access to GenCC through an API is coming soon. We recommend anyone interested in access to the API, or would like to be notified when early access is available, to <a id="click-signup" href="https://creationproject.us7.list-manage.com/subscribe?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank" class="no-underline hover:underline text-blue-800 ">signup</a> so the GenCC can keep you informed.</p>
      <a id="click-signup" href="https://creationproject.us7.list-manage.com/subscribe?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="_blank" class="o-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 ">
        <i class="fas fa-mail-bulk"></i> Signup to keep informed
      </a>
    </div>
  </div>
</div>

@endsection
