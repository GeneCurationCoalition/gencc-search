<div wire:poll>
    {{-- Care about people's approval and you will be their prisoner. --}}
    {{-- <span>
        <i class="animate-spin fas fa-spinner"></i> Processing submissions...

    </span> --}}
    <button wire:click="start" class=" border-green-800 border-2 bg-green-100 hover:bg-green-300 font-bold mx-auto p-1 px-3 rounded focus:outline-none focus:shadow-outline float-right" type="button"><i class="fas fa-running"></i> Process Submissions</button>
    @if($messages)
    <div>
       <pre>{{ $messages }}</pre>
    </div>
    @endif
</div>
