<?php

namespace App\Traits;

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
    $data = $data->toArray();
    $return = "0";

    // if($data != 0) {
    //   return "data";
    //   return $return;
    // }

    if ($action == 'count') {
      return $data[$field];
    }

    if ($data[$field] == 0) {
      return $return;
    }

    if(!empty($data)) {
      $return = ($data[$field] / $data['count_submissions']) * 100;
        //dd($return);
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
      //dd($return);
    }
    return $return;
  }





  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displayGroupSubmissionsByMondoDisease($data)
  {
    // $data->whereHas('diseases', function (Builder $data) use ($value) {

    //dd($data);
    foreach($data as $item) {
      //dd($item);
      foreach ($item->diseases->where("type", "MONDO") as $element) {
        //  dd($element);
        $mondo[$element->id]["title"] =  $element->title;
        $mondo[$element->id]["curie"] =  $element->curie;
        $mondo[$element->id]["submissions"][$item->id] = $item;
      }
    }
    $data = collect($mondo);
    //dd($mondo);
    $data->all();
    //dd($data);
    return $data;
  }


  /**
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
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
   * Return a displayable string of date parameter
   *
   * @param
   * @return string
   */
  public function displaySortSubmissionsByClassification($data)
  {
    //dd($data);
    $sorted = $data->sortBy('classification_id');
    return $sorted;
  }

  /**
   * Return a displayable string of date parameter
   *
   * @param
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
    $filtered = $data->where('type', "MONDO");
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


  // /**
  //  * Return a displayable string of date parameter
  //  *
  //  * @param
  //  * @return string
  //  */
  // public function displaySubmissionsInListingTable($data = null, $href = "#", $format = "long")
  // {
  //   if (empty($data))
  //     return '';

  //     $list = array(
  //       "definitive"                    => "0",
  //       "strong"                        => "0",
  //       "moderate"                      => "0",
  //       "limited"                       => "0",
  //       "disputed"             => "0",
  //       "refuted"              => "0",
  //       "animal-model-only"             => "0",
  //       "no-known-disease-relationship" => "0",
  //       "nul"                           => "0"
  //     );

  //     //dd($data);
  //   foreach($data as $val) {
  //     // Take the val and add one to it.
  //     $list[$val->classification->slug]    = $list[$val->classification->slug] + 1;
  //   }
  //   //dd($list);
  //   $css = "rounded-full py-1 text-xs px-1 text-center text-white";
  //   $css_num = "500";
  //   $css_nul = "bg-gray-300";
  //   $return = "
  //     <div class='grid grid-cols-9 gap-2'>
  //           <div class='{$css} ". ($list['definitive'] != 0 ? 'bg-red-'.$css_num : $css_nul) ."'><a href='{$href}' title='Definitive'>{$list['definitive']}</a></div>
  //           <div class='{$css} " . ($list['strong'] != 0 ? 'bg-orange-' . $css_num : $css_nul) . "'><a href='{$href}' title='Strong'>{$list['strong']}</a></div>
  //           <div class='{$css} " . ($list['moderate'] != 0 ? 'bg-yellow-' . $css_num : $css_nul) . "'><a href='{$href}' title='Moderate'>{$list['moderate']}</a></div>
  //           <div class='{$css} " . ($list['limited'] != 0 ? 'bg-green-' . $css_num : $css_nul) . "'><a href='{$href}' title='Limited'>{$list['limited']}</a></div>
  //           <div class='{$css} " . ($list['disputed'] != 0 ? 'bg-teal-' . $css_num : $css_nul) . "'><a href='{$href}' title='Disputed Evidence'>{$list['disputed']}</a></div>
  //           <div class='{$css} " . ($list['refuted'] != 0 ? 'bg-blue-' . $css_num : $css_nul) . "'><a href='{$href}' title='Refuted Evidence'>{$list['refuted']}</a></div>
  //           <div class='{$css} " . ($list['animal-model-only'] != 0 ? 'bg-indigo-' . $css_num : $css_nul) . "'><a href='{$href}' title='Animal Model Only'>{$list['animal-model-only']}</a></div>
  //           <div class='{$css} " . ($list['no-known-disease-relationship'] != 0 ? 'bg-purple-' . $css_num : $css_nul) . "'><a href='{$href}' title='No Known Disease Relationship'>{$list['no-known-disease-relationship']}</a></div>
  //           <div class='{$css} " . ($list['nul'] != 0 ? 'bg-gray-' . $css_num : $css_nul) . "'><a href='{$href}' title='Unset'>{$list['nul']}</a></div>
  //     </div>
  //   ";
  //   return ($return);

  // }

}