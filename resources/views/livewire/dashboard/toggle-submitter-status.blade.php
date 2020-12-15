<div>
    @if($submitter->status == 1)
        <button wire:click="disable('{{ $submitter->uuid }}')" class=" bg-green-400 text-gray-100 font-bold mx-auto p-2 rounded-full">Show On Website Submitter List [Click to hide]</button>
    @else
        <button wire:click="enable('{{ $submitter->uuid }}')" class=" bg-red-400 text-gray-100 font-bold mx-auto p-1/2 p-2 rounded-full">Hidden From Submitter List - Submissions will still show [Click to show]</button>
    @endif
</div>
