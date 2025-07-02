@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">About GenCC</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="_blank" href="https://thegencc.org/faq.html"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="gencc-about">
    <section class="why-gencc mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Why is the GenCC needed?</h2>
        <p class="mb-4 text-gray-700 leading-relaxed">
            Several groups and resources provide information that pertains to the validity of gene-disease relationships. 
            However, the standards, terminologies, and assessment criteria used to define these relationships are still evolving. 
            The Gene Curation Coalition (GenCC) was formed to try to address this need.
        </p>
        <p class="mb-4 text-gray-700 leading-relaxed">
            The GenCC brings together groups interested in evaluating the clinical validity of gene-disease relationships, 
            with the goal of sharing data publicly and developing consistent assessment terminology.
        </p>
    </section>

    <section class="gencc-goals mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">The goals of the GenCC are as follows:</h2>
        <ul class="list-disc list-inside space-y-2 text-gray-700 leading-relaxed ml-4">
            <li>Clarify the overlap between gene curation efforts</li>
            <li>Understand different curation processes and classification systems</li>
            <li>Develop consistent terminology for validity assessment</li>
            <li>Collaborate on gene curation projects</li>
        </ul>
    </section>

    <section class="steering-committee mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Steering Committee</h2>
        <div class="committee-members space-y-4">
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Heidi Rehm</h3>
                <p class="text-gray-600">Chief Genomics Officer - Massachusetts General Brigham Personalized Medicine</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Teri Manolio</h3>
                <p class="text-gray-600">Director - National Human Genome Research Institute</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Sharon Plon</h3>
                <p class="text-gray-600">Professor - Baylor College of Medicine</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Michael Bamshad</h3>
                <p class="text-gray-600">Professor - University of Washington</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Christine Eng</h3>
                <p class="text-gray-600">Professor - Baylor College of Medicine</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Cynthia Powell</h3>
                <p class="text-gray-600">Professor - University of North Carolina at Chapel Hill</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Ada Hamosh</h3>
                <p class="text-gray-600">Professor - Johns Hopkins University School of Medicine</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Helen Firth</h3>
                <p class="text-gray-600">Consultant Clinical Geneticist - Cambridge University Hospitals</p>
            </div>
            <div class="member">
                <h3 class="font-semibold text-lg text-gray-800">Steven Harrison</h3>
                <p class="text-gray-600">Director of Genomic Medicine - Partners HealthCare</p>
            </div>
        </div>
    </section>

    <div class="disclaimer bg-gray-100 p-4 rounded-lg">
        <p class="text-sm text-gray-600 italic">
            The information on this website is not intended for direct diagnostic use or medical decision-making 
            without review by a genetics professional. Individuals should not change their health behavior 
            solely on the basis of information contained on this website.
        </p>
    </div>
</div>
@endsection