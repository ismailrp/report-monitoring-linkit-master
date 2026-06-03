<x-filament-panels::page>
    <div class="fi-page-content-widgets space-y-6">

        {{-- Filter card --}}
        <div class="fi-widget">
            <x-filament::section>
                <x-slot name="heading">
                    Filter Alert Renewal
                </x-slot>

                <form>
                    {{ $this->form }}
                </form>
            </x-filament::section>
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
