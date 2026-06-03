<x-filament-panels::page>
    <div class="fi-page-content-widgets space-y-6">

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::card>
                <div>
                    <div class="text-sm font-medium text-gray-500">Total Today</div>
                    <div class="text-2xl font-semibold">
                        {{ number_format($this->total_today, 0, ',', '.') }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div>
                    <div class="text-sm font-medium text-gray-500">Total Yesterday</div>
                    <div class="text-2xl font-semibold">
                        {{ number_format($this->total_yesterday, 0, ',', '.') }}
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div>
                    <div class="text-sm font-medium text-gray-500">Total Drop</div>
                    <div class="text-2xl font-semibold">
                        {{ number_format($this->total_drop, 0, ',', '.') }}
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Filter card --}}
        <div class="fi-widget">
            <x-filament::section>
                <x-slot name="heading">
                    Filter Alerts
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
