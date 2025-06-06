<div>
    {{-- The Master doesn't talk, he acts. --}}
    <table class="min-w-full">
        <thead>
            <tr class="border-t border-gray-200">
            <th class="pl-8 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                File </th>
            <th class="px-2 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">

            </th>
            <th class="">

            </th>
            <th class="hidden md:table-cell px-2 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Date Submitter Created
            </th>
            <th class="hidden md:table-cell px-2 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Added By
            </th>
            <th class="hidden md:table-cell px-2 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Last Processed
            </th>
            <th class="pr-2 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Last Modified</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">

            @foreach ($submitter->submission_files as $file)

            <tr class=" @if($file->updated_at->diffForHumans() == "1 second ago") bg-green-100 @endif align-top">
            <td class="hidden md:table-cell pl-8 text-sm text-center leading-5 text-gray-500 pt-3 whitespace-nowrap">
                @if($file->status == 1)
                    <button wire:click="processed('{{ $file->uuid }}')" class="whitespace-nowrap bg-green-800 hover:bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">To Process</button>
                @elseif($file->status == 0)
                    <button wire:click="toprocess('{{ $file->uuid }}')" class=" bg-red-800 hover:bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Processed</button>
                @else
                    ARCHIVED
                @endif

            </td>
            <td class="px-4 py-3 max-w-0 w-full whitespace-no-wrap text-sm leading-5  font-medium text-gray-900">

                <a href="{{ route('manage-submitter-show-file', [$submitter->uuid,$file->uuid]) }}">
                {!!Str::limit($file->file_name, 60, '...')!!}
                </a>
                <a title="{{ $file->file_name }}">
                    <i class="fas fa-info-circle"></i>
                </a>
                    @if($file->private_notes)

                    <div class="w-full text-xs p-1">Private Notes: <pre>{{ $file->private_notes }}</pre></div>

                    @endif

            </td>

            <td class="hidden md:table-cell px-2 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                <a href="{{ route('manage-submitter-show-file', [$submitter->uuid,$file->uuid]) }}">
                <i class="fas fa-edit text-black"></i>
                @if($file->private_notes)<i class="fas fa-file-alt text-green-700"></i>@endif
                </a>
            </td>
            <td class="hidden md:table-cell px-2 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                @if($file->submitted_run_date){{ $file->submitted_run_date->format('m/d/Y') }}@endif
            </td>
            <td class="hidden md:table-cell px-2 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                {{ $file->created_by->name ?? ''}}
            </td>
            <td class="hidden md:table-cell px-2 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                @if(isset($file->processed_last_at))
                {{ $file->processed_last_at->format('m/d/Y')}}
                @endif
            </td>
            <td class="hidden md:table-cell px-2 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                {{ $file->updated_at->format('m/d/Y') }}
                {{-- {{ $file->updated_at->diffForHumans() }} --}}
            </td>
            <td>
                @if($file->status == 0 || $file->status == 1)
                    <button wire:click="archive('{{ $file->uuid }}')" class="bg-gray-500  my-3 hover:bg-gray-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Archive</button>
                @else
                    <button wire:click="processed('{{ $file->uuid }}')" class="bg-gray-800  my-3 hover:bg-gray-600 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Unarchive</button>
                @endif
            </td>
            </tr>

            @endforeach
            <!-- More project rows... -->
        </tbody>
        </table>

</div>
