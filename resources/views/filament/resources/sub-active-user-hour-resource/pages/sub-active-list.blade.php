<x-filament::page>
    <div class="space-y-4">
        <div class="p-4 bg-white shadow rounded-xl">
            <h2 class="text-lg font-semibold mb-2">Filter</h2>
            {{ $this->form }}
        </div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament::page>
