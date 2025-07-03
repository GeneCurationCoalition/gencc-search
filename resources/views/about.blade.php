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
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
    <!-- Left column: Why and Goals -->
    <div>
    <div class="mt-0">
        <!-- https://v1.tailwindcss.com/docs/upgrading-to-v1#17-double-check-your-default-line-height -->
        <h2 class="my-3">Why is the GenCC needed?</h2>
        <p class="mb-4 text-gray-900 leading-normal">
            Several groups and resources provide information that pertains to the validity of gene-disease relationships; however, the standards and terminologies to define the evidence base for a gene’s role in disease are still evolving and the community is in need of trusted and harmonized sources that define the level of evidence for a gene’s role in disease. To tackle this issue, the Gene Curation Coalition (GenCC) was formed.
        </p>
        <p class="mb-4 text-gray-900 leading-normal">
            The Gene Curation Coalition brings together groups engaged in the evaluation of gene-disease validity with a willingness to share data publicly, to develop consistent terminology for gene curation activities and to facilitate the consistent assessment of genes that have been reported in association with disease.
        </p>
    </div>

    <div class="col-12 mt-10"><hr /></div>

    <div class="mt-10">
        <h2 class="my-3">The goals of the GenCC are as follows:</h2>
        <ul class="space-y-2 text-gray-900 leading-normal ml-6 pl-4" style="list-style-type: disc !important; list-style-position: outside !important;">
            <li>Clarify the overlap between gene curation efforts</li>
            <li>Understand the aims, processes, information used, classification systems, and users of the different curation efforts</li>
            <li>Develop consistent terminology for validity assessment as well as inheritance, allelic requirement, and mechanism of disease</li>
            <li>Collaborate on gene curation projects</li>
        </ul>
    </div>
    </div>

    <!-- Right column: Steering Committee -->
    <div class="mt-10 lg:mt-0">
    <h2 class="my-3">Steering Committee</h2>
    <table class="w-full border-collapse">
        <tbody>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Heidi Rehm</div>
                    <div class="text-lg italic">GenCC Co-Chair</div>
                    ClinGen
                </td>

            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Marina DiStefano</div>
                    <div class="text-lg italic">GenCC Co-Chair</div>
                    ClinGen
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Fowzan Alkuraya</div>
                    King Faisal Specialist Hospital and Research Center
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Christina Austin-Tse</div>
                    Broad CMG
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Marie Balzotti</div>
                    Myriad Women's Health
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Elspeth Bruford</div>
                    HGNC
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Alison Coffey</div>
                    Illumina
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Yaron Einhorn</div>
                    Franklin by Genoox
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Helen Firth</div>
                    DECIPHER
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Ada Hamosh</div>
                    OMIM
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Sarah Hunt</div>
                    EMBL-EBI
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Yunyun Jiang</div>
                    Invitae
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Teri Klein</div>
                    PharmGKB
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Kalotina Machini</div>
                    Laboratory for Molecular Medicine
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Kelly Radtke</div>
                    Ambry
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Ana Rath</div>
                    Orphanet
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Catherine Snow</div>
                    Genomics England PanelApp
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">Zornitza Stark</div>
                    PanelApp Australia
                </td>
            </tr>
            <tr>
                <td class="border border-gray-300 px-4 py-2 text-gray-900">
                    <div class="font-semibold text-xl">James Ware</div>
                    Gene2Phenotype (G2P)
                </td>
            </tr>

        </tbody>
    </table>
    </div>
</div>

@endsection
