@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC Terms of Use</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-8 mt-6">
    <div>
        <p class="mb-4">
            The GenCC data are available free of restriction under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication. The GenCC requests that you give attribution to GenCC and the contributing sources whenever possible and appropriate. The accepted Flagship manuscript is now available from Genetics in Medicine (https://www.gimjournal.org/article/S1098-3600(22)00746-8/fulltext).
        </p>
        <h2 class="">Example Attribution Statements</h2>
        <p class="mb-4">
            The following curated content was obtained from the Gene Curation Coalition (www.thegencc.org) which includes contributions from the following organizations: Australian Genomics, ClinGen, ….. Gene-Disease Validity classifications [date accessed].
        </p>
        <p class="mb-4">
            The authors would like to thank the Gene Curation Coalition (GenCC) for generating curated content used in this project. GenCC’s curated content was obtained at www.thegencc.org [date accessed] and includes contributions from the following organizations: Australian Genomics, ClinGen, ….
        </p>
        <h2 class="">Citing the GenCC</h2>
        <p class="mb-4">
            To cite a specific content on our website, please use the following format:
        </p>
        <p class="mb-4">
            The Gene Curation Coalition. URL [date accessed].
        </p>
    </div>
</div>

@endsection
