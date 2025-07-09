@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">GenCC FAQ</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-8 mt-6">
    <div>
        <h1 class="my-3">Common Questions</h1>
        <!-- <h1 class="">Common Questions</h1> -->
        <!-- <h1 class="my-3 font-bold">Common Questions</h1> -->
        <!-- <h1 class="my-3 text-4xl font-bold">Common Questions</h1> -->

        @include('partials.general.about-why-goals')


        <div class="col-12 mt-10 mb-8"><hr /></div>

        <!-- <div class="mt-10"> -->
        <h2 class="my-3">What is gene-disease validity curation?</h2>
          <p class="mb-4">
            Gene-disease validity curation answers the question “Is a gene associated with disease?” A curation evaluates the evidence for a claim for a specific gene, disease, and mode of inheritance.
          </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>

        <h2 class="my-3">How do I cite/acknowledge the GenCC for database use?</h2>
          <p class="mb-4">
            The GenCC data are available free of restriction under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication. The GenCC requests that you give attribution to GenCC and the contributing sources whenever possible and appropriate. The accepted Flagship manuscript is now available from Genetics in Medicine (https://www.gimjournal.org/article/S1098-3600(22)00746-8/fulltext).
          </p>
          <p class="mb-4">
            Example Attribution Statements:
          </p>
          <p class="mb-4">
            The following curated content was obtained from the Gene Curation Coalition (www.thegencc.org) which includes contributions from the following organizations: Australian Genomics, ClinGen, ….. Gene-Disease Validity classifications [date accessed].
          </p>
          <p class="mb-4">
            The authors would like to thank the Gene Curation Coalition (GenCC) for generating curated content used in this project. GenCC’s curated content was obtained at www.thegencc.org [date accessed] and includes contributions from the following organizations: Australian Genomics, ClinGen, ….
          </p>
          <p class="mb-4">
            Citing the GenCC:
          </p>
          <p class="mb-4">
            To cite a specific content on our website, please use the following format:
          </p>
          <p class="mb-4">
            The Gene Curation Coalition. URL [date accessed].
          </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Can I submit to the database?</h2>
        <p class="mb-4">
          The GenCC does not currently accept individual submissions but new groups can join per guidance below.
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Can my group join the GenCC?</h2>
        <p class="mb-4">
          Groups seeking to join the GenCC must perform their own gene curations and 1) have content that the GenCC SC considers useful to the mission of GenCC; 2) be willing to share their curations publicly on the website; 3) adhere to ClinGen gene curation standards (Strande et al 2017), an equally rigorous framework, or provide a widely used existing public gene-level resource (e.g. OMIM, Orphanet); and 4) be able to use our standardized clinical validity terms and disease ontologies for their submissions. If you feel that your group meets this curation standard, please contact us to inquire about joining the GenCC.
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Can I follow my favorite gene?</h2>
        <p class="mb-4">
          You cannot currently follow your favorite gene, but this feature will be available soon in a future release.
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Can I download the data from this site?</h2>
        <p class="mb-4">
          Yes, there are download buttons available on multiple screens throughout the website. However, due to licensing restrictions, a GenCC download does not include OMIM data. OMIM data can be accessed and downloaded through https://www.omim.org/
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Can I search using previous HGNC names or symbols?</h2>
        <p class="mb-4">
          You cannot currently search using old gene symbols, but this feature will be available soon. Please consult HGNC (www.genenames.org) to confirm that you are using the most current gene symbol for searches.
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Are HGNC gene symbols and names stable?</h2>
        <p class="mb-4">
          HGNC are committed to making as few changes to gene symbols as possible (https://blog.genenames.org/Stable_Symbols), but some updates may still be necessary. See https://www.ncbi.nlm.nih.gov/pmc/articles/PMC7494048/ for situations that merit a symbol update. To keep symbols stable, sometimes changes will be made to the gene name only.
        </p>

        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h2 class="my-3">Some submitters with large public resources have very small data sets. Why is this?</h2>
        <p class="mb-4">
          To test the process, some GenCC submitters have so far submitted only a small subset of their curation data. This data set will continue to expand over the coming months
        </p>


        <div class="col-12 mt-10 mb-8"><hr /></div>
        <h1 class="my-3">Website Pages FAQ</h1>

        <div class="col-12 mt-10"><hr /></div>

        <h1 class="my-3">Validity terms/Delphi Survey</h1>




    </div>
</div>

@endsection
