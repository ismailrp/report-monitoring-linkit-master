<x-filament-panels::page>
    <div class="fi-page-content-widgets space-y-6">
        {{-- Card untuk Filter --}}
        <div class="fi-widget">
            <x-filament::section>
                <x-slot name="heading">
                    Filter
                </x-slot>

                {{-- Form: Filament form renders here. --}}
                <form wire:submit.prevent="apply">
                    {{ $this->form }}
                </form>
            </x-filament::section>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total Revenue</div>
                        <div class="text-2xl font-semibold">
                            {{ number_format($this->total_revenue, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-primary-500">
                        <x-heroicon-o-currency-dollar class="w-10 h-10" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total MT</div>
                        <div class="text-2xl font-semibold">
                            {{ number_format($this->total_mt, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-success-500">
                        <x-heroicon-o-arrow-down-tray class="w-10 h-10" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total MO</div>
                        <div class="text-2xl font-semibold">
                            {{ number_format($this->total_mo, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-info-500">
                        <x-heroicon-o-arrow-up-tray class="w-10 h-10" />
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Tabel --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
