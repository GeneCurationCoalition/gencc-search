<div class="border-t border-t-gray-200 border-t-solid pt-0 mt-2 text-sm">
                      <div class="grid grid-cols-11 gap-2 my-3 ">

                          <div class="col-span-11 xl:col-span-1 -mt-1"><a href="{{ route('submission-show', $item->uuid) }}">{!!  $item->displayCurationLabelPill($item->classification) !!}</a></div>
                          <div class="col-span-9 xl:col-span-4  ml-0 mr-3">
                            <div class="flex">
                              <div class="flex-initial mr-1">
                                <i class="fas fa-dna text-gray-400"></i>
                              </div>
                              <div class="flex-initial leading-tight">
                                <a class="list-text-label" href="{{ route('gene-show', $item->gene->curie) }}"> {{ $item->gene->title }}</a>
                                <div class="text-sm text-gray-600">{!! $item->displayLinkToHgnc($item->gene->curie, $item->gene->curie) !!}</div>
                              </div>
                              <div class="flex-initial ml-4 mr-1">
                                <i class="far fa-disease text-gray-400"></i>
                              </div>
                              <div class="flex-initial break-words">
                                <div class="list-text-label"> {{ $item->displayMondoDisease($item->diseases)->first()->title }}</div>
                                <div class="text-sm text-gray-600">{!! $item->displayLinkToMondo($item->displayMondoDisease($item->diseases)->first()->curie, $item->displayMondoDisease($item->diseases)->first()->curie) !!}</div>
                                @if($item->displayMondoDisease($item->diseases)->first()->curie != $item->disease->curie)
                                <div class="mt-1 text-sm text-gray-600 break-words"> Submitted as: {!! $item->displayLinkToOmim($item->disease->curie, $item->disease->curie) !!}</div>
                                @endif
                              </div>
                            </div>

                          </div>
                          <div class="col-span-2 xl:col-span-1"><span class="list-text-label"> {{ $item->inheritance->abbreviation }} <i class="far fa-question-circle text-gray-400" title="{{ $item->inheritance->title }} Mode Of Inheritance " data-toggle="tooltip" data-placement="top" \></i></span>
                          </div>
                          <div class="col-span-11 xl:col-span-1"><i class="far fa-calendar text-gray-400"></i> <span class="list-text-label">{{ Carbon\Carbon::parse($item->submitted_as_date)->format('m/d/Y') }}</span><div class="text-sm text-gray-600 ml-4">Evaluated</div>
                        <div class="text-sm mt-3 text-gray-600 ml-4 font-semibold leading-snug">{{ $item->created_at->format('m/d/Y') }}<div class=" font-normal">Submitted</div></div>
                      </div>
                          <div class="col-span-8 xl:col-span-4 "><a href="{{ route('submitter-show', $item->submitter->uuid) }}" class=""><i class="far fa-building text-gray-400"></i> <span class="list-text-label">{{ $item->submitter->title }}</span></a>
                            <div class="ml-4 pt-1 text-sm flex-inline-list">
                              <ul>
                                @if($item->submitted_as_public_report_url)
                              <li><a target="assertion_criteria_url" href="{{ $item->submitted_as_public_report_url }}" class="text-blue-600">Public Report <i class="fas fa-external-link-alt"></i></a></li>
                                @endif
                                @if($item->submitted_as_assertion_criteria_url)
                              <li><a target="assertion_criteria_url" href="{{ $item->submitted_as_assertion_criteria_url }}" class="text-blue-600">Assertion Criteria <i class="fas fa-external-link-alt"></i></a></li>
                                @endif
                              <li><a class="text-blue-600" href="{{ route('submission-show', $item->uuid) }}">More Details <i class="far fa-arrow-alt-circle-right"></i></a></li>
                              </ul>
                            </div>
                            @if($item->submitted_as_notes)
                            <div class="ml-4 pt-1 text-sm text-gray-800">Evidence: {!! \Illuminate\Support\Str::limit($item->submitted_as_notes, 100, $end='... <a class="text-gray-600 underline" href="'. route('submission-show', $item->uuid) .'">Read more <i class="far fa-arrow-alt-circle-right"></i></a>') ?? ''!!}</div>
                            @endif


                          </div>
                      </div>
                  </div>