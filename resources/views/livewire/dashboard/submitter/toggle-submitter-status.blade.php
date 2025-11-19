<div>
    <div class="md:flex md:items-center mb-6">
        <div class="md:w-1/6">
            <label class="block text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                Hide from Submitter List
            </label>
        </div>
        <div class="md:w-5/6 flex items-center">
            <div class="flex items-center">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" wire:model="status" value="1" {{ $status == 1 ? 'checked' : '' }} class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 focus:ring-2">
                    <span class="ml-2 text-sm font-medium text-gray-700">Show</span>
                </label>
                <label class="inline-flex items-center cursor-pointer ml-40">
                    <input type="radio" wire:model="status" value="0" {{ $status == 0 ? 'checked' : '' }} class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 focus:ring-2">
                    <span class="ml-2 text-sm font-medium text-gray-700">Hide</span>
                </label>
            </div>
        </div>
    </div>
</div>
