<x-filament-panels::page>
    <div class="fi-page-content-widgets space-y-6">
        {{-- Filter Section --}}
        <div class="fi-widget">
            <x-filament::section>
                <x-slot name="heading">
                    Filter
                </x-slot>

                <form>
                    {{ $this->form }}
                </form>
            </x-filament::section>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total SubActive</div>
                        <div class="text-2xl font-semibold">
                            {{ number_format($this->total_sub_active, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-primary-500">
                        <x-heroicon-o-users class="w-10 h-10" />
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
                        <x-heroicon-o-paper-airplane class="w-10 h-10" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Avg Percentage</div>
                        <div class="text-2xl font-semibold">
                            {{ number_format($this->avg_percentage, 2, ',', '.') }} %
                        </div>
                    </div>
                    <div class="text-warning-500">
                        <x-heroicon-o-chart-pie class="w-10 h-10" />
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- Data Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
