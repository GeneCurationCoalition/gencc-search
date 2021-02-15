<div class="border-t border-t-gray-200 border-t-solid pt-0 mt-2 text-sm
                            @if($item->status == 1)
                            @elseif($item->status == 0)
                               bg-red-200
                            @else
                               bg-gray-300
                            @endif">
                      <div class="grid grid-cols-11 gap-2 my-3">

                          <div class="col-span-11 xl:col-span-1 -mt-1"><a href="{{ route('submission-show', $item->uuid) }}">{!!  $item->displayCurationLabelPill($item->classification) !!}</a></div>
                          <div class="col-span-9 xl:col-span-4  ml-0 mr-3">
                            <div class="flex">
                              <div class="flex-initial leading-tight mr-2">
                                <a class="list-text-label" href="{{ route('gene-show', $item->gene->curie) }}"> {{ $item->gene->title }}</a>
                                <div class="text-sm text-gray-600">{!! $item->displayLinkToHgnc($item->gene->curie, $item->gene->curie) !!}</div>
                              </div>
                              <div class="flex-initial break-words">
                                <div class="list-text-label"> {{ $item->disease->title }}</div>
                                <div class="text-sm text-gray-600">{!! $item->displayLinkToDisease($item->disease->curie, $item->disease->curie) !!}</div>
                                {{-- @if($item->displayMondoDisease($item->diseases)->first()->curie != $item->disease->curie)
                                <div class="mt-1 text-sm text-gray-600 break-words"> Submitted as: {!! $item->displayLinkToOmim($item->disease->curie, $item->disease->curie) !!}</div>
                                @endif --}}
                                @if($item->disease->id != $item->disease_original->id)
                                  <div class="mt-1 text-sm text-gray-600 break-words"> Submitted as: {!! $item->displayLinkToDisease($item->disease_original->curie, $item->disease_original->curie) !!}</div>
                                  @endif
                              </div>
                            </div>

                          </div>
                          <div class="col-span-2 xl:col-span-1"><span class="list-text-label"> {{ $item->inheritance->abbreviation }} <i class="far fa-question-circle text-gray-400" title="{{ $item->inheritance->title }} Mode Of Inheritance " data-toggle="tooltip" data-placement="top" \></i></span>
                          </div>
                          <div class="col-span-11 xl:col-span-2"><span class="list-text-label">{{ Carbon\Carbon::parse($item->submitted_as_date)->format('m/d/Y') }}</span><div class="text-sm text-gray-600">Evaluated</div>
                        <div class="text-sm mt-3 text-gray-600 font-semibold leading-snug">@if($item->submitted_run_date) {{ Carbon\Carbon::parse($item->submitted_run_date)->format('m/d/Y') }} @else N/A @endif<div class=" font-normal">Submitted</div></div>
                      </div>
                          <div class="col-span-8 xl:col-span-2 "><a href="{{ route('submitter-show', $item->submitter->uuid) }}" class="list-text-label"><span class=" text-blue-700">{{ $item->submitter->title }}</span></a>
                            <div class="pt-1 text-sm ">
                              <ul>
                                @if($item->submitted_as_public_report_url)
                              <li><a id='click-exit-public-report'  target="_blank" href="{{ $item->submitted_as_public_report_url }}" class="text-blue-700">Public Report </a></li>
                                @endif
                                @if($item->submitted_as_assertion_criteria_url)
                              <li><a id='click-exit-assertion-criteria'  target="_blank" href="{{ $item->submitted_as_assertion_criteria_url }}" class="text-blue-700">Assertion Criteria </a></li>
                                @endif
                              <li><a class="text-blue-700" href="{{ route('submission-show', $item->uuid) }}">More Details <i class="far fa-arrow-alt-circle-right"></i></a></li>
                              </ul>
                            </div>
                            @if($item->submitted_as_notes)
                            <div class="ml-4 pt-1 text-sm text-gray-800">Evidence: {!! \Illuminate\Support\Str::limit($item->submitted_as_notes, 100, $end='... <a class="text-gray-600 underline" href="'. route('submission-show', $item->uuid) .'">Read more <i class="far fa-arrow-alt-circle-right"></i></a>') ?? ''!!}</div>
                            @endif


                          </div>

                          <div class="col-span-2 xl:col-span-1">
                            {{-- <span class="list-text-label"> {{ $item->status }} </span> --}}
                            @if($item->status == 1)
                                <button wire:click="disable('{{ $item->uuid }}')" class=" bg-green-800 hover:bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Enabled</button>
                            @elseif($item->status == 0)
                                <button wire:click="archive('{{ $item->uuid }}')" class=" bg-red-800 hover:bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Disabled</button>
                            @else
                                <button wire:click="enable('{{ $item->uuid }}')" class="bg-gray-500 hover:bg-gray-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Archived</button>
                            @endif

                            <div class="mt-3">
                              <a class=" border-blue-800 border bg-white text-blue-400 font-bold p-1/2 px-2 rounded-full" href="{{ route('manage-submitter-show-submission', [$item->submitter->uuid, $item->uuid]) }}">EDIT</a>
                            </div>
                          </div>

                            @if($item->private_notes)
                            <div class="col-span-2 text-right text-orange-800 p-1">Private Notes:</div>
                            <div class="col-span-8 text-orange-800 p-1 bg-gray-100"><pre class="text-xs">{!! $item->private_notes !!}</pre></div>
                            @endif
                      </div>
                  </div>