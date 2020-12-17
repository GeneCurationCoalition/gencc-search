<!-- This example requires Tailwind CSS v2.0+ -->
<div class="fixed z-40 inset-0 overflow-y-auto">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <!--
      Background overlay, show/hide based on modal state.

      Entering: "ease-out duration-300"
        From: "opacity-0"
        To: "opacity-100"
      Leaving: "ease-in duration-200"
        From: "opacity-100"
        To: "opacity-0"
    -->
    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
      <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
    </div>

    <!-- This element is to trick the browser into centering the modal contents. -->
    <span class="hidden inline-block align-middle h-screen" aria-hidden="true">&#8203;</span>
    <!--
      Modal panel, show/hide based on modal state.

      Entering: "ease-out duration-300"
        From: "opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        To: "opacity-100 translate-y-0 sm:scale-100"
      Leaving: "ease-in duration-200"
        From: "opacity-100 translate-y-0 sm:scale-100"
        To: "opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    -->
    <div class="inline-block align-middle transform transition-all" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
      <div class="bg-white rounded-lg px-10 pt-5 text-left overflow-hidden shadow-xl mt-8 align-middle max-w-4xl p-6">
        <div>
        <div class="mx-auto flex items-center justify-center">
          <!-- Heroicon name: check -->
          <a href="https://thegencc.org/faq.html#public-beta" ><img src="/brand/icons/beta.png" class=" hover:scale-150  transition duration-200 ease-in-out transform  absolute w-24 ml-40 -mt-8 pl-10" /></a>
          <img src="/brand/logo/genecc-logo.jpg" class=" h-20" />
        </div>
        <div class="mt-3 text-center sm:mt-5">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
            A global effort to harmonize gene-level resources.
          </h3>
          <div class="mt-2 text-left">
            <p class="text-sm pb-2 text-gray-900">
              All data here are released under a <a class="text-blue-700 underline"  href='https://web.archive.org/web/20141107034939/http://www.wellcome.ac.uk/stellent/groups/corporatesite/@policy_communications/documents/web_document/wtd003207.pdf' target="_blank" id='click-exit-fort-lauderdale-agreement' >Fort Lauderdale Agreement</a> for the benefit of the wider biomedical community. You can freely download and search the data, and we encourage the use and publications of validity data for specific targeted sets of genes. However, we ask that you not publish global (site-wide) analyses of these data, or of large gene sets, until after the GenCC flagship paper has been published (estimated to be spring 2021). After the flagship publication, data will be available free of restriction under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication. The GenCC requests that you give attribution to GenCC and the contributing sources whenever possible and appropriate.
            </p>
            <p class="text-sm pb-2 text-gray-900">
The GenCC maintains this database to provide information pertaining to the validity of gene-disease relationships. The GenCC comprises organizations that currently provide online resources (e.g. ClinGen, DECIPHER, Genetics Home Reference, Genomics England PanelApp, OMIM, Orphanet, PanelApp Australia, TGMI’s G2P), as well diagnostic laboratories that have committed to sharing their internal curated gene-level knowledge (e.g. Ambry, Illumina, Invitae, Myriad Women’s Health, Partners Laboratory for Molecular Medicine).
            </p>
 <p class="text-sm pb-2 text-gray-900">
Member groups submit assertions about gene-disease relationships. Each entry will be an assertion for a gene, disease, and a mode of inheritance with a link to evidence supporting that assertion. Different displays within the database group assertions by submitter, by disease, by gene, and by clinical validity.
 </p>
 <p class="text-sm pb-2 text-gray-900">
To harmonize terms describing gene-disease validity, the GenCC used a Delphi method to survey both members of our GenCC organizations and the international genetics community. Terms that were agreed upon are “Definitive, Strong, Moderate, Limited, Disputed Evidence, Refuted Evidence, Animal Model Only, and No Known Disease Relationship”. Data is mapped to these terms unless a resource did not curate to the same level of granularity as the harmonized list and therefore a broader category of “Supportive” was used to represent a basic level of evidence for gene-disease association such as that used by OMIM and Orphanet. <a class="text-blue-700 underline" href='https://thegencc.org/faq' target="_blank">Please see this page for more information on these terms.</a>
 </p>

            </p>
            <hr class="mt-4 mb-4" />
            <div class="text-left text-xs">
              <p>@include("partials.terms.general-disclaimer")</p>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-5 sm:mt-6 ">
        <button  wire:click="dismiss" type="button" class="inline-flex justify-center w-full rounded-lg shadow-sm px-4 py-2 border-4 bg-gray-200 border-blue-600 text-xl text-blue-800 hover:text-white hover:bg-blue-800">
          Accept &amp; Continue
        </button>
      </div>
      </div>
      <div class="pt-0 pb-4 text-left overflow-hidden shadow-xl mb-8 align-middle max-w-4xl w-full px-10">
        <div class=" bg-blue-800 shadow-inner rounded-lg rounded-t-none px-4 py-3 bg-opacity-75 border-gray-100 border-4 border-t-0">
          <p class="text-gray-100 text-center font-medium"><i class="fas fa-mail-bulk"></i> Sign-up and stay informed when new data and features are available.</p>
          <div class="flex">
            {{-- <div class="flex-none pt-4 text-gray-100">
               Sign-Up With Email:
            </div> --}}
            <div class="flex-1 ">
              @include("partials.stay-informed.mailchimp-simple-form")
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>