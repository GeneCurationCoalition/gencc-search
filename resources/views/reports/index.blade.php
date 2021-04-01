@extends('layouts.app')


@section('headline')
    <div class="grid grid-cols-12 gap-0">
      <div class="col-span-10 text-white"><h1 class=" truncate">Reports</h1></div>
      <div class="col-span-2 pt-4 align-bottom">
        <div class="text-right mt-4"><a class="px-3" href="https://thegencc.org/faq.html#stats-index"><i class="fas fa-question-circle"></i> Help</a></div>
      </div>
  </div>
@endsection

@section('content')
<div class="mt-10">
  <div class="grid lg:grid-cols-1 gap-4 lg:gap-20 xl:gap-40 mb-6">
        <div>
          {{ $trios->count() ?? "00" }}
            <table class="table-auto align-top w-full">
            @forelse ($trios as $trio)
              <tr class="border-b">
                <td class="align-top  py-3 px-2">
                  <a href="{{ route("gene-show", [$trio->gene->curie]) }}">{{ $trio->gene->title }}</a>
                  <div class="text-sm text-gray-500">{{ $trio->gene->curie }}</div>
                </td>
                <td class="align-top  py-3 px-2">
                  {{ $trio->disease->title }}
                  <div class="text-sm text-gray-500">{{ $trio->disease->curie }}</div>
                </td>
                <td class="align-top  py-3 px-2">
                  {{ $trio->inheritance->abbreviation }}
                  <div class="text-sm text-gray-500">{{ $trio->inheritance->curie }}</div>
                </td>
                <td class="align-top  py-3 px-2 whitespace-no-wrap">
                  {{ $trio->submissions->count() }} Count
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100001")
                        @php $groupA = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-definitive">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100002")
                        @php $groupA = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-strong">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100003")
                        @php $groupA = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-moderate">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100009")
                        @php $groupA = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-supportive">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100004")
                        @php $groupB = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-limited">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100005")
                        @php $groupB = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-disputed">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100006")
                        @php $groupB = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-refute">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100007")
                        @php $groupB = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-animalmodelonly">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2">
                  @php $count = 0; @endphp
                  @if($trio->submissions->count())
                  @foreach ($trio->submissions as $item)
                      @if($item->classification->curie == "GENCC:100008")
                        @php $groupB = true; @endphp
                        @php $count++ @endphp
                      @endif
                  @endforeach
                  @endif
                    @if($count)
                      <div class="block rounded-full py-half text-xs px-1 border-gray-400  border text-center text-white gencc-noknown">{{ $count ?? "0" }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="align-top  py-3 px-2 whitespace-no-wrap">
                  @isset($groupA)
                  @isset($groupB)
                  @if(($groupA == true) && ($groupB == true))
                    <i class="fas fa-exclamation-triangle"></i> Conflict
                  @endif
                  @endisset
                  @endisset
                  @php $groupA = false; @endphp
                  @php $groupB = false; @endphp
                </td>
              </tr>
            @empty

            @endforelse
            </table>
        </div>

  </div>
</div>

@endsection
