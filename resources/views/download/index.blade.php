@extends('layouts.app')


@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Data</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" href="https://thegencc.org/faq.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10">
  <div class="grid lg:grid-cols-2 gap-4 lg:gap-20 xl:gap-40 mb-6">
        <div>
          <h2>Data Download</h2>
          <p class="mb-3">Member groups submit assertions about gene-disease relationships. Each entry will be an assertion for a gene, disease, and a mode of inheritance with a link to evidence supporting that assertion. Member groups have submitted data and will continue to add to the data set over time. Due to licensing restrictions, a GenCC download does not include OMIM data. OMIM data can be accessed and downloaded through <a id='click-exit-omim' href="https://www.omim.org/" class="underline" target="_blank">https://www.omim.org/</a></p>
          <div class="grid grid-cols-2 gap-4 mb-6">
            <a id="download-submissions-export-xlsx" href="{{ route('submissions-export-xlsx') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
            <a id="download-submissions-export-xls" href="{{ route('submissions-export-xls') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (xls)</a>
            <a id="download-submissions-export-tsv" href="{{ route('submissions-export-tsv') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (tsv)</a>
            <a id="download-submissions-export-csv" href="{{ route('submissions-export-csv') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (csv)</a>
          </div>
        </div>

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
