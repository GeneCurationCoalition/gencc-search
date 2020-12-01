<div>
    <form wire:submit.prevent="save">
    <input type="file" wire:model="file">

    @error('file') <span class="error">{{ $message }}</span> @enderror

    <button class="btn btn-primarybg-blue-500 hover:bg-blue-700 text-gray-100 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Upload File</button>
</form>
</div>
