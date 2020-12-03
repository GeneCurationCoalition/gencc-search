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
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
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
      <div class="bg-white rounded-lg px-10 pt-5 text-left overflow-hidden shadow-xl  sm:mt-8 sm:align-middle sm:max-w-3xl sm:w-full p-6">
        <div>
        <div class="mx-auto flex items-center justify-center">
          <!-- Heroicon name: check -->
          <a href="https://thegencc.org/resources/faq/#public-beta" ><img src="/brand/icons/beta.png" class=" hover:scale-150  transition duration-200 ease-in-out transform  absolute w-24 ml-40 -mt-8 pl-10" /></a>
          <img src="/brand/logo/genecc-logo.jpg" class=" h-20" />
        </div>
        <div class="mt-3 text-center sm:mt-5">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
            A global effort to harmonize gene-level resources.
          </h3>
          <div class="mt-2">
            <p class="text-sm text-gray-900">
              This GenCC resource provides information pertaining to the validity of gene-disease relationships ipsum, dolor sit amet consectetur adipisicing elit. Eius aliquam laudantium explicabo pariatur iste dolorem animi vitae error totam. At sapiente aliquam accusamus facere veritatis.
            </p>
            <hr class="mt-4 mb-4" />
            <div class="text-left text-sm">
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
      <div class="pt-0 pb-4 text-left overflow-hidden shadow-xl sm:mb-8 sm:align-middle sm:max-w-3xl sm:w-full sm:px-10">
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