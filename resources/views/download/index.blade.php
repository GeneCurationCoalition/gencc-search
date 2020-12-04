@extends('layouts.app')


@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Data</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" target="gencc-help" href="https://thegencc.org/resources/help.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10">
  <div class="grid lg:grid-cols-2 gap-4 lg:gap-20 xl:gap-40 mb-6">
        <div>
          <h2>Data Download</h2>
          <p class="mb-3">Member groups submit assertions about gene-disease relationships. Each entry will be an assertion for a gene, disease, and a mode of inheritance with a link to evidence supporting that assertion. Different displays within the database group assertions by submitter, by disease, by gene, and by clinical validity.</p>
          <p class="mb-3">To harmonize terms describing gene-disease validity, the GenCC used a Delphi method to survey both members of our GenCC organizations and the international genetics community. Terms that were agreed upon are “Definitive, Strong, Moderate, Limited, Disputed Evidence, Refuted Evidence, Animal Model Only, and No Known Disease Relationship”. </p>
          <p class="mb-6">Due to licensing restrictions, a GenCC download only includes a partial data set. Curations from OMIM are not included in this set.</p>
          <div class="grid grid-cols-2 gap-4 mb-6">
            <a href="{{ route('submissions-export-xlsx') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (xlsx)</a>
            <a href="{{ route('submissions-export-xls') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (xls)</a>
            <a href="{{ route('submissions-export-tsv') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (tsv)</a>
            <a href="{{ route('submissions-export-csv') }}" class="no-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 "><i class="fas fa-file-download"></i> Submissions (csv)</a>
          </div>
        </div>

        <div>
          <h2>API Access</h2>
          <p class="mb-4">Access to GenCC through and API will be released in early 2021. We recommend anyone interested in access to the API, or would like to be notified when early access is available, to <a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="gencc-faq" class="no-underline hover:underline text-blue-800 ">signup</a> so the GenCC to keep you informed.</p>
          <a href="https://creationproject.us7.list-manage.com/subscribe/post?u=47520fb4e4a2c9edfc44a61af&id=7ccf9c9b09" target="gencc-faq" class="o-underline block text-center hover:underline text-blue-800 bg-blue-100 rounded-full text-lg py-2 px-4 leading-tight border-2 border-blue-800 ">
							<i class="fas fa-mail-bulk"></i> Stay Informed About GenCC's New Features
						</a>
        </div>
  </div>
</div>

@endsection
