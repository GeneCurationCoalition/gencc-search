<div>
    {{-- The Master doesn't talk, he acts. --}}
    <table class="min-w-full">
        <thead>
            <tr class="border-t border-gray-200">
            <th class="pl-8 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                File </th>
            <th class="px-4 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">

            </th>
            <th class="hidden md:table-cell px-6 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Submitter Run Date
            </th>
            <th class="hidden md:table-cell px-6 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                File Added By
            </th>
            <th class="pr-6 py-3 border-b border-gray-200 bg-gray-50 text-right text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                Date Uploaded</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">

            @foreach ($submitter->submission_files as $file)

            <tr class="@if($file->updated_at->diffForHumans() == "1 second ago") bg-green-100 @endif">
            <td class="hidden md:table-cell pl-8 text-sm text-center leading-5 text-gray-500">
                @if($file->status == 1)
                    <button wire:click="disable('{{ $file->uuid }}')" class=" bg-green-800 hover:bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Enabled</button>
                @elseif($file->status == 0)
                    <button wire:click="archive('{{ $file->uuid }}')" class=" bg-red-800 hover:bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Disabled</button>
                @else
                    <button wire:click="enable('{{ $file->uuid }}')" class="bg-gray-500 hover:bg-gray-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Archived</button>
                @endif
            </td>
            <td class="px-4 py-3 max-w-0 w-full whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                {{ $file->file_name }}
            </td>
            <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                @if($file->submitted_run_date){{ $file->submitted_run_date->format('m/d/Y') }}@endif
            </td>
            <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                {{ $file->created_by->name ?? ''}}
            </td>
            <td class="hidden md:table-cell px-6 py-3 whitespace-no-wrap text-sm leading-5 text-gray-500 text-right">
                {{ $file->updated_at->format('m/d/Y') }}
                {{-- {{ $file->updated_at->diffForHumans() }} --}}
            </td>
            </tr>

            @endforeach
            <!-- More project rows... -->
        </tbody>
        </table>

</div>
