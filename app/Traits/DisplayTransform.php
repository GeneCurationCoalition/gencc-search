<?php

namespace App\Traits;

use App\Classification;
use Illuminate\Database\Eloquent\Collection;

trait DisplayTransform
{


  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayLinkToHgnc($text, $href, $css = null, $target = "_blank", $options = null)
  {
    return "<a class='{$css} text-gray-600' id='click-exit-hgnc-term'  target='{$target}' href='https://www.genenames.org/data/gene-symbol-report/#!/hgnc_id/{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
  }

  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayLinkToMondo($text, $href, $css=null, $target = "_blank", $options = null)
  {

    return "<a class='{$css} text-gray-600' id='click-exit-mondo-term' target='{$target}' href='https://monarchinitiative.org/disease/{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
  }

  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayLinkToOmim($text, $href, $css =null, $target = "_blank", $options = null)
  {
    $href = str_replace("OMIM:", "", $href);
    return "<a class='{$css} text-gray-600' id='click-exit-omim-term' target='{$target}' href='https://omim.org/entry/{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
  }

  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayLinkToMoi($text, $href, $css = null, $target = "_blank", $options = null)
  {
    return "<a class='{$css} text-gray-600'  id='click-exit-moi-term' target='{$target}' href='https://hpo.jax.org/app/browse/term/{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
  }



  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayLinkToDisease($text, $href, $css = null, $target = "_blank", $options = null)
  {
    $ontology = explode(":", $href);

    switch ($ontology[0]) {
      case "MONDO":
        $href = 'https://monarchinitiative.org/disease/' . $href;
        break;
      case "OMIM":
        $href = str_replace("OMIM:", "", $href);
        $href = 'https://omim.org/entry/'. $href;
        break;
      case "Orphanet":
        $href = str_replace("Orphanet:", "Orpha:", $href);
      case "Orpha:":
        $href = str_replace("Orpha:", "", $href);
        $href = 'https://www.orpha.net/consor/cgi-bin/OC_Exp.php?lng=EN&Expert='. $href;
        break;
    }

    return "<a class='{$css} text-gray-600' target='{$target}' href='{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
  }


  /**
   * Return a reformat the data for the submission expansion on the main gene listing page
   *
   * @param
   * @return string
   */
  public function displayGeneSubmitterSubmissions($item,  $var = null)
  {

    //dd($item);
    //$item = Gene::curie($curie)->firstOrFail();

    $submission_objects = $item->submissions
      ->sortBy(fn ($submission) => Classification::priority($submission->classification->curie) ?? PHP_INT_MAX);
    $submitter_submissions = $submission_objects->groupBy([
      'submitter.title',
      function ($item) {
        return $item->classification->title;
      },
    ], $preserveKeys = false);

    $this->submitter_submissions = $submitter_submissions;

    // Collect the diseases
    $diseases = new Collection();
    foreach ($item->submissions as $element) {
      $diseases->push($element->disease);
    }
    $diseases = $diseases->flatten(1);
    $diseases->values()->all();
    //$diseases = $diseases->where('type', 'MONDO');
    //$diseases = $diseases->unique('curie');
    //dd($diseases);
    $this->diseases = $diseases;

    // Support the toggle
    if ($this->display != true) {
      $this->display                           = true;
    } else {
      $this->display                           = false;
    }

    return $submitter_submissions;

  }


  public function displayDiseaseMondo($item,  $var = null)
  {
    $item = collect($item);
    // Support both old string type ('MONDO') and new integer type constant
    $item = $item->filter(function ($disease) {
        return $disease->type === 'MONDO' || $disease->type === \App\Disease::TYPE_MONDO;
    })->first();
    return $item;
  }


  /**
   * Return a deprecation indicator icon for deprecated diseases
   *
   * Displays an orange warning icon with tooltip when a disease has
   * STATUS_DEPRECATED (8). The tooltip shows the deprecated_name if available.
   *
   * @param \App\Disease|null $disease The disease object to check
   * @return string HTML for the deprecation indicator, or empty string if not deprecated
   */
  public function displayDeprecationIndicator($disease)
  {
    if (!$disease || $disease->status !== \App\Disease::STATUS_DEPRECATED) {
      return '';
    }

    $tooltip = 'DEPRECATED: This disease term is deprecated';
    if (!empty($disease->deprecated_name)) {
      $tooltip = 'DEPRECATED: ' . e($disease->deprecated_name);
    }

    return '<span class="ml-1" style="color: #f97316; cursor: help;" title="' . $tooltip . '">⚠</span>';
  }

}
