<?php

namespace App\Traits;

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

    $submission_objects = $item->submissions->sortBy('classification.order');
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
    $item = $item->where('type', 'MONDO')->first();
    return $item;
  }



  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayCurationsCountFilter($type, $var)
  {
    $test = '<button class="text-green-600 rounded-full h-8 border-2 mt-1 w-100 border-gray-300 bg-gray-200" wire:click="toggle_curations_definitive">'.$var.'</button>';
    return $test;
  }

}
