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
  @if(!$downloads_available)
  <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
    <div class="flex">
      <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-red-500"></i>
      </div>
      <div class="ml-3">
        <p class="text-sm text-red-700">
          <strong>Downloads Temporarily Unavailable:</strong>
          Release archives are currently unavailable. Please check back later.
        </p>
      </div>
    </div>
  </div>
  @endif

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
      @if($downloads_available)
      <div class="grid grid-cols-2 gap-4 mb-6">
        <a id="download-submissions-export-xlsx-new" href="{{ route('submissions-export-xlsx') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
        <a id="download-submissions-export-tsv-new" href="{{ route('submissions-export-tsv') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (tsv)</a>
        <a id="download-submissions-export-csv-new" href="{{ route('submissions-export-csv') }}?format=new" class="no-underline block text-center hover:underline text-green-800 bg-green-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-green-800"><i class="fas fa-file-download"></i> Submissions (csv)</a>
      </div>
      @else
      <div class="grid grid-cols-2 gap-4 mb-6 opacity-50 pointer-events-none">
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (xlsx)</span>
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (tsv)</span>
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (csv)</span>
      </div>
      @endif
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
      @if($downloads_available)
      <div class="grid grid-cols-2 gap-4 mb-6">
        <a id="download-submissions-export-xlsx" href="{{ route('submissions-export-xlsx') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
        <a id="download-submissions-export-tsv" href="{{ route('submissions-export-tsv') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (tsv)</a>
        <a id="download-submissions-export-csv" href="{{ route('submissions-export-csv') }}" class="no-underline block text-center hover:underline text-gray-700 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-400"><i class="fas fa-file-download"></i> Submissions (csv)</a>
      </div>
      @else
      <div class="grid grid-cols-2 gap-4 mb-6 opacity-50 pointer-events-none">
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (xlsx)</span>
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (tsv)</span>
        <span class="block text-center text-gray-400 bg-gray-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-gray-300"><i class="fas fa-file-download"></i> Submissions (csv)</span>
      </div>
      @endif
    </div>
  </div>

  {{-- Programmatic Access Section --}}
  <div class="mb-6">
    <h2>Programmatic Access</h2>
    <p class="mb-4 text-sm text-gray-700">
      Downloads are served with <code class="text-xs bg-gray-100 px-2 py-1 rounded">ETag</code>
      and <code class="text-xs bg-gray-100 px-2 py-1 rounded">Last-Modified</code> headers and
      support conditional requests to avoid re-downloading unchanged files. Downloads count
      against a per-IP daily quota of
      <strong>{{ config('downloads.daily_quota') }} per day</strong>;
      <code class="text-xs bg-gray-100 px-2 py-1 rounded">304 Not Modified</code> responses do not
      count, and <code class="text-xs bg-gray-100 px-2 py-1 rounded">HEAD</code>
      requests are fully exempt.
    </p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
      <p class="text-sm font-semibold text-blue-800 mb-3">
        <i class="fas fa-exchange-alt mr-1"></i> Response Headers &amp; Conditional Request Headers
      </p>
      <ul class="space-y-3 text-sm text-blue-900">
        <li>
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">ETag</code>
          <span class="mx-1 text-blue-600">&rarr;</span>
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">If-None-Match</code>
          &mdash; The server returns an MD5 hash of the file in the
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">ETag</code> header on every response.
          Send it back in an <code class="text-xs bg-blue-100 px-2 py-1 rounded">If-None-Match</code>
          header on subsequent requests; if the file hasn&#8217;t changed, the server responds with
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">304 Not Modified</code>.
        </li>
        <li>
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">Last-Modified</code>
          <span class="mx-1 text-blue-600">&rarr;</span>
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">If-Modified-Since</code>
          &mdash; The server returns the file&#8217;s release timestamp in the
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">Last-Modified</code> header on every response.
          Send it back in an <code class="text-xs bg-blue-100 px-2 py-1 rounded">If-Modified-Since</code>
          header on subsequent requests; if the file hasn&#8217;t changed, the server responds with
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">304 Not Modified</code>.
        </li>
        <li>
          <i class="fas fa-info-circle text-blue-600 mr-1"></i>
          <strong>HEAD requests are quota-exempt.</strong>
          A <code class="text-xs bg-blue-100 px-2 py-1 rounded">HEAD</code> request returns all
          response headers &mdash; including <code class="text-xs bg-blue-100 px-2 py-1 rounded">ETag</code> and
          <code class="text-xs bg-blue-100 px-2 py-1 rounded">Last-Modified</code> &mdash; without
          downloading the file, and does not count against the daily limit.
        </li>
      </ul>
    </div>

    <details class="border border-gray-200 rounded-lg">
      <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg select-none">
        <i class="fas fa-terminal mr-2 text-gray-500"></i>curl / wget examples
      </summary>
      <div class="px-4 pb-4 pt-2 space-y-4">

        <div>
          <p class="text-xs text-gray-600 mb-1 font-medium">Step 1 &mdash; fetch the file and capture headers (new CSV format):</p>
          <pre class="bg-gray-900 text-green-300 text-xs rounded p-3 overflow-x-auto" style="color: #68d391">curl -D headers.txt \
  -o gencc-submissions.csv \
  "{{ route('submissions-export-csv') }}?format=new"</pre>
        </div>

        <div>
          <p class="text-xs text-gray-600 mb-1 font-medium">Step 2a &mdash; on subsequent runs, use ETag to skip unchanged files:</p>
          <pre class="bg-gray-900 text-green-300 text-xs rounded p-3 overflow-x-auto" style="color: #68d391">ETAG=$(grep -i '^ETag:' headers.txt | sed 's/ETag: //i' | tr -d '"\r\n')

curl -D headers.txt \
  -H "If-None-Match: \"$ETAG\"" \
  --write-out "%{http_code}\n" \
  -o gencc-submissions.csv \
  "{{ route('submissions-export-csv') }}?format=new"
# HTTP 304 = no change, file not re-downloaded; HTTP 200 = new file saved</pre>
        </div>

        <div>
          <p class="text-xs text-gray-600 mb-1 font-medium">Step 2b &mdash; alternatively, use Last-Modified:</p>
          <pre class="bg-gray-900 text-green-300 text-xs rounded p-3 overflow-x-auto" style="color: #68d391">LAST_MODIFIED=$(grep -i '^Last-Modified:' headers.txt | sed 's/Last-Modified: //i' | tr -d '\r\n')

curl -D headers.txt \
  -H "If-Modified-Since: $LAST_MODIFIED" \
  --write-out "%{http_code}\n" \
  -o gencc-submissions.csv \
  "{{ route('submissions-export-csv') }}?format=new"</pre>
        </div>

        <div>
          <p class="text-xs text-gray-600 mb-1 font-medium">Lightweight poll with HEAD (quota-exempt):</p>
          <pre class="bg-gray-900 text-green-300 text-xs rounded p-3 overflow-x-auto" style="color: #68d391">curl -I "{{ route('submissions-export-csv') }}?format=new"</pre>
        </div>

        <div>
          <p class="text-xs text-gray-600 mb-1 font-medium">wget (--timestamping handles If-Modified-Since automatically):</p>
          <pre class="bg-gray-900 text-green-300 text-xs rounded p-3 overflow-x-auto" style="color: #68d391">wget --timestamping \
  "{{ route('submissions-export-csv') }}?format=new"</pre>
        </div>

        <p class="text-xs text-gray-500 mt-2">
          <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
          Data is updated weekly &mdash; polling more than once per day provides no benefit.
          ETag matching is more reliable than timestamp matching and is preferred.
        </p>

      </div>
    </details>
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
