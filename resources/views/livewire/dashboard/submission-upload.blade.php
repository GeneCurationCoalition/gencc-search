<div>
    @error('file') <span class="error">{{ $message }}</span> @enderror
    @if(!$size)
        <form wire:submit.prevent="save">
            <input type="file" wire:model="file" class="border-blue-500 border hover:bg-blue-100 text-gray-900 font-bold py-1 px-2 rounded focus:outline-none focus:shadow-outline">
            <button class="bg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Upload File</button>
            <div wire:loading wire:target="photo">Uploading...</div>
        </form>
    @else
        <button wire:click="clear" class="bg-orange-500 hover:bg-orange-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="button">Upload another file...</button>
    @endif
</div>
