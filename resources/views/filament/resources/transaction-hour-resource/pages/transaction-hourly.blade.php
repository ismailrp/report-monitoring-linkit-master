{{-- <x-filament-panels::page>
    {{ $this->table }}
</x-filament-panels::page> --}}


<x-filament-panels::page>
    <div class="fi-page-content-widgets space-y-6">
        {{-- Card untuk Filter --}}
        <div class="fi-widget">
            <x-filament::section>
                <x-slot name="heading">
                    Filters
                </x-slot>

                <form>
                    {{ $this->form }}
                </form>
            </x-filament::section>
        </div>

        {{-- Tabel di bawah card --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
