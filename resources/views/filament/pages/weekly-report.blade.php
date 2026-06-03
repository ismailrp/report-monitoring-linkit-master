<x-filament::page>
    {{-- Card Filter --}}
    <x-filament::card class="mb-6">
        <div class="flex flex-wrap items-center gap-4">
            {{ $this->form }}
        </div>
    </x-filament::card>

    {{-- Table --}}
    {{ $this->table }}
</x-filament::page>
