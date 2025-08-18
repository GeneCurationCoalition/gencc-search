  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">{{ $gene->title }}</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right text-md mb-1"><a href="{{ route("genes") }}" class="rounded-full py-1 px-3 text-center border-2 border-solid border-blue-900 text-blue-700 bg-white whitespace-no-wrap hover:bg-blue-200"><i class="far fa-arrow-alt-circle-left"></i> Return to list</a></div>
        <div class="text-right text-md mb-1">
          <form onsubmit="searchGenes(event)" class="inline-block">
            <input type="text" id="gene-search" placeholder="Search genes..." class="rounded-full py-1 px-2 text-center border border-solid border-blue-900 text-blue-700 bg-white text-sm focus:bg-blue-200 focus:outline-none">
          </form>
        </div>
        <script>
          function searchGenes(event) {
            event.preventDefault();
            const searchTerm = document.getElementById('gene-search').value;
            if (searchTerm.trim()) {
              window.location.href = '{{ route("genes") }}?title=' + encodeURIComponent(searchTerm);
            } else {
              window.location.href = '{{ route("genes") }}';
            }
          }
        </script>
        <div class="text-right"><a class="px-3" target="_blank" href="{{ route('faq') }}#website-pages-faq"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
