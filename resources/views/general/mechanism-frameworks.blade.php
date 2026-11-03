@extends('layouts.app')

@section('headline')
  <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">Mechanism Frameworks</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-6"><a class="px-3" target="_blank" href="{{ route('faq') }}"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-8 mt-6">
    <div>
        <p class="mb-4">
            The Gene Curation Coalition has developed a semi-quantitative metric to determine the mechanism of disease for gene-disease relationships. The framework is intended to be used for human gene-disease relationships that have scored at Moderate or higher using the ClinGen gene curation framework (Strande et al 2017, see <a href="https://clinicalgenome.org/docs/gene-disease-validity-standard-operating-procedure/" target="_blank" class="text-blue-600 hover:text-blue-800">here</a> for the most recent gene curation SOP). Levels should be capped at “Suspected” if applying to genes with Limited or below evidence. Caution should be exercised if adapting to non-human genetic diseases as this framework has not been tested using non-human phenotypes.
        </p>
        <p class="mb-4">
            **While these are semi-quantitative frameworks, please note that points are not statistical in nature, nor do they correspond to Bayesian points in the sequence variant classification frameworks.
        </p>

        <h2 class="my-3">Frameworks</h2>

        <ul class="space-y-2 ml-4 pl-4 mb-4" style="list-style-type: disc !important; list-style-position: outside !important;">
            <li>
                <a href="{{ asset('docs/mechanism-frameworks/gencc-lof-framework-v1.0.pdf') }}" target="_blank" class="text-blue-600 hover:text-blue-800">Gene Curation Coalition (GenCC) Recommendation for Loss of Function Mechanism Curation - Version 1.0, November 2026</a>
                <span class="text-gray-600 text-sm">(PDF)</span>
            </li>
            <li>
                <a href="{{ asset('docs/mechanism-frameworks/gencc-gof-framework-v1.0.pdf') }}" target="_blank" class="text-blue-600 hover:text-blue-800">Gene Curation Coalition (GenCC) Recommendation for Gain of Function Mechanism Curation - Version 1.0, November 2026</a>
                <span class="text-gray-600 text-sm">(PDF)</span>
            </li>
            <li>
                <a href="{{ asset('docs/mechanism-frameworks/gencc-dn-framework-v1.0.pdf') }}" target="_blank" class="text-blue-600 hover:text-blue-800">Gene Curation Coalition (GenCC) Recommendation for Dominant Negative Mechanism Curation - Version 1.0, November 2026</a>
                <span class="text-gray-600 text-sm">(PDF)</span>
            </li>
            <li>
                <a href="{{ asset('docs/mechanism-frameworks/gencc-not-lof-framework-v1.0.pdf') }}" target="_blank" class="text-blue-600 hover:text-blue-800">Gene Curation Coalition (GenCC) Recommendation for NOT Loss of Function Mechanism Curation - Version 1.0, November 2026</a>
                <span class="text-gray-600 text-sm">(PDF)</span>
            </li>
        </ul>
    </div>
</div>
@endsection
