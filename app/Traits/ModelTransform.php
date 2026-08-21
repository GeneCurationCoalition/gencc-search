<?php

namespace App\Traits;

use App\Disease;

trait ModelTransform
{


  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayStatChartBarPercentSubmitter($data, $field, $action = false)
  {
    $return = "0";

    // Access the attribute directly instead of converting to array (much faster)
    $fieldValue = $data->$field ?? 0;

    if ($action == 'count') {
      return $fieldValue;
    }

    if ($fieldValue == 0) {
      return $return;
    }

    $countSubmissions = $data->count_submissions ?? 0;
    if ($countSubmissions > 0) {
      $return = ($fieldValue / $countSubmissions) * 100;
    }

    return $return;
  }


  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayStatChartBarPercent($data, $total)
  {
    $return = "0";
    if (!empty($data)) {
      $return = ($total / $data) * 140;
    }

    return max(0, min(100, $return));
  }



  /**
   * Group submissions by classification curie.
   *
   * @param Collection $data
   * @param object $classification
   * @return Collection
   */
  public function displayGroupSubmissionsByClassification($data, $classification)
  {
    //dd($classification);
    $sorted = $data->sortBy('curie');
    //dd($sorted);
    switch ($classification->curie) {
      case "GENCC:100001":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100001");
        break;
      case "GENCC:100002":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100002");
        break;
      case "GENCC:100003":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100003");
        break;
      case "GENCC:100004":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100004");
        break;
      case "GENCC:100005":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100005");
        break;
      case "GENCC:100006":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100006");
        break;
      case "GENCC:100007":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100007");
        break;
      case "GENCC:100008":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100008");
        break;
      case "GENCC:100009":
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100009");
        break;
      default:
        $grouped = $sorted->classification->whereIn('curie', "GENCC:100000");
        break;
    }
    $grouped->all();
    //dd($grouped);
    return $grouped;
  }

  /**
   * Return a displayable pill for curation label.
   *
   * @param object $data
   * @param mixed $label
   * @param string|null $action
   * @return string
   */
  public function displayCurationLabelPill($data, $label = null, $action = null)
  {
    //dd($data);
    switch ($data->slug) {
      case "definitive":
        $color  = "gencc-definitive";
        break;
      case "strong":
        $color  = "gencc-strong";
        break;
      case "moderate":
        $color  = "gencc-moderate";
        break;
      case "limited":
        $color  = "gencc-limited";
        break;
      case "disputed":
        $color  = "gencc-disputedevidence";
        break;
      case "refuted":
        $color  = "gencc-refutedevidence";
        break;
      case "animal-model-only":
        $color  = "gencc-animalmodelonly";
        break;
      case "no-known":
        $color  = "gencc-noknowndiseaserelationship";
        break;
      case "supportive":
        $color  = "gencc-supportive";
        break;
      default:
        $color  = "gencc-nul";
        break;
    }
    //return ($data);
    if($action == null){
      $text = $data->title;
    } elseif($action == "filter") {
      $text = (!$label) ? 0 : $label;
      $return = "<div class='transition duration-200 ease-in-out transform hover:scale-110 text-sm col-12 my-2 text-gray-700'><i class='far fa-check-square text-green-700'></i> $data->title <span class='float-right text-gray-600 px-1 rounded-full border text-xs border-gray-300 bg-gray-200'>{$text}</span></div>";
      return ($return);
    } else {
      $text = (!$label) ? 0 : $label;
      $color = ($text == 0) ? "gencc-nul w-10" : $color ." w-10";
    }
    $return = "<div class='transition duration-200 ease-in-out transform hover:scale-110 rounded-full py-1 text-xs px-1 text-center text-white {$color}'>{$text}</div>";
    return ($return);
  }


  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayMondoDisease($data)
  {
    // Support both old string type ('MONDO') and new integer type constant
    $filtered = $data->filter(function ($disease) {
        return $disease->type === 'MONDO' || $disease->type === Disease::TYPE_MONDO;
    });
    return $filtered;
    //dd($data);
  }



  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayCurationCountPill($data, $type = null, $href = "#")
  {
    if ($data == 0) {
      // if zero just make it grey
      $color = "gencc-zero";
      $text  = "text-gray-400";
    } else {
      // Chekc the type to see what color it should be
      switch ($type) {
        case "definitive":
          $color  = "gencc-definitive";
          $text  = "text-white";
          break;
        case "strong":
          $color  = "gencc-strong";
          $text  = "text-white";
          break;
        case "moderate":
          $color  = "gencc-moderate";
          $text  = "text-white";
          break;
        case "limited":
          $color  = "gencc-limited";
          $text  = "text-white";
          break;
        case "disputed":
          $color  = "gencc-disputedevidence";
          $text  = "text-white";
          break;
        case "refuted":
          $color  = "gencc-refutedevidence";
          $text  = "text-white";
          break;
        case "supportive":
          $color  = "gencc-supportive";
          $text  = "text-white";
          break;
        case "animal-model-only":
          $color  = "gencc-animalmodelonly";
          $text  = "text-white";
          break;
        case "no-known":
          $color  = "gencc-noknowndiseaserelationship";
          $text  = "text-white";
          break;
        default:
          $color  = "gencc-nul";
          $text  = "text-gray-400";
          break;
      }
    }
    //return ($data);
    $return = "<a href='{$href}' class='transition duration-200 ease-in-out transform hover:scale-125 block rounded-full py-half text-xs px-1 border-gray-400  border text-center {$text} {$color}' title='{$data} submissions are {$type}'  data-toggle='tooltip' data-placement='top'>{$data}</a>";
    return ($return);
  }
}
