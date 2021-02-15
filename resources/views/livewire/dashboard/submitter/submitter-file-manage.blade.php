<div>
    <div class="grid grid-cols-12 mt-4 gap-0">
    <div class="col-span-12">
      <div class="grid grid-cols-12 gap-0">


        @if (session()->has('message'))
        <div class="col-span-12 ml-8 mb-4 bg-green-200" >
            <span class="m-2 inline-block text-green-700"> {{ session('message') }}</span>
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show-files', $file->submitter->curie) }}" class="text-white">Return to list</a></div>
        </div>
        @else
        <div class="col-span-12 ml-8 mb-4 " >
            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show-files', $file->submitter->curie) }}" class="text-white">Return to list</a></div>
        </div>
        @endif


        <div class="col-span-3 pt-3 text-right pr-3">File:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{ $file->file_name }}
            </div>
        <div class="col-span-3 pt-3 text-right pr-3">UUID:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{ $file->uuid }}
            </div>
        <div class="col-span-3 pt-3 text-right pr-3">Submitter:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{ $file->submitter->title }}
            </div>
        <div class="col-span-3 pt-3 text-right pr-3">Status:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{-- {{ $file->status }} --}}
                @if($file->status == 1)
                    <button wire:click="disable('{{ $file->uuid }}')" class=" bg-green-800 hover:bg-green-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Enabled</button>
                @elseif($file->status == 0)
                    <button wire:click="archive('{{ $file->uuid }}')" class=" bg-red-800 hover:bg-red-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Disabled</button>
                @else
                    <button wire:click="enable('{{ $file->uuid }}')" class="bg-gray-500 hover:bg-gray-400 text-gray-100 font-bold mx-auto p-1/2 px-2 rounded-full focus:outline-none focus:shadow-outline" type="button">Archived</button>
                @endif

            </div>
        <div class="col-span-3 pt-3 text-right pr-3">Last Processed At:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{ $file->processed_last_at ?? "--"}}
            </div>
        <div class="col-span-3 pt-3 text-right pr-3">Date Created By Submitter:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                {{ $file->submitted_run_date }}
            </div>
        <div class="col-span-3 pt-4 text-right pr-3">Private Notes:</div>
            <div class="col-span-9 py-1 my-2 border-l-8 pl-3">
                <form wire:submit.prevent="save">
                    <textarea wire:model.defer="file.private_notes" placeholder="Submission file notes" class="w-full border border-gray-400 p-2">{{ $file->private_notes }}</textarea>
                    <button class="bg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Save Notes...</button>

                            <div class="inline text-white bg-gray-700 float-right text-center px-3 py-2"><a href="{{ route('manage-submitter-show-files', $file->submitter->curie) }}" class="text-white">Return to list</a></div>
                </form>
            </div>
        </div>
        </div>
        </div>
</div>
