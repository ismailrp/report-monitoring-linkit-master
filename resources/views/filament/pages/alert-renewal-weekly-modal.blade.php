{{-- Operator-level Aggregated Status Summary --}}
<div class="mb-5">
    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
        Total Status Summary (Aggregated — All Services, All Dates)
    </h3>

    @if (isset($statuses) && count($statuses) > 0)
        <div class="overflow-x-auto rounded-lg shadow ring-1 ring-black/5 dark:ring-white/10">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 w-1/2 text-[11px] uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 font-medium text-right text-gray-500 dark:text-gray-400 w-1/2 text-[11px] uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5 bg-white dark:bg-gray-900 font-medium">
                    @foreach ($statuses as $status)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">
                                {{ $status->status !== null ? $status->status : 'N/A' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-900 dark:text-white">
                                {{ number_format((float) $status->total_status, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="mb-6 p-4 text-center bg-gray-50 dark:bg-white/5 rounded-lg border border-dashed border-gray-300 dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No aggregated status summary available.</p>
        </div>
    @endif
</div>

</div>

{{-- Weekly Drop Breakdown per Service --}}
<h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Weekly AVG Breakdown</h3>
<div class="overflow-x-auto rounded-lg shadow ring-1 ring-black/5 dark:ring-white/10">
    <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Checkpoint</th>
                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Service</th>
                <th class="px-4 py-3 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Week Now</th>
                <th class="px-4 py-3 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Week Before</th>
                <th class="px-4 py-3 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">AVG</th>
                <th class="px-4 py-3 font-medium text-center text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
            @forelse($services as $item)
                @php
                    $rate = $item->country?->convert_usd ?? 1;
                    if (strtoupper($item->country?->country ?? '') === 'OMAN') {
                        $rate = $rate / 1000;
                    }
                    $todayVal = (float) $item->today * $rate;
                    $yesterdayVal = (float) $item->yesterday * $rate;
                    $dropPercent = $yesterdayVal > 0
                        ? (($yesterdayVal - $todayVal) / $yesterdayVal) * 100
                        : 0;

                    // Key: "date|id_service" — normalize date to Y-m-d string
                    $svcDate = $item->date instanceof \Carbon\Carbon ? $item->date->format('Y-m-d') : substr((string) $item->date, 0, 10);
                    $svcKey = (string)$svcDate . '|' . (string)$item->id_service;
                    $currentServiceStatuses = collect($serviceStatuses->get($svcKey, []));
                @endphp
                <tr x-data="{ open: false }">
                    <td class="px-4 py-3 align-top">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-gray-900 dark:text-white uppercase">
                                {{ \Carbon\Carbon::parse($item->date)->format('D, d M') }}
                            </span>
                            <span class="text-[10px] text-gray-500 uppercase tracking-tight">
                                HOUR {{ $item->hour }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white align-top font-medium">
                        {{ $item->service?->service ?? 'Unknown' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white align-top font-medium">
                        {{ number_format($todayVal, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white align-top font-medium">
                        {{ number_format($yesterdayVal, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold align-top {{ $dropPercent > 10 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                        {{ number_format($dropPercent, 2) }}%
                    </td>
                    <td class="px-4 py-3 text-center align-top">
                        <button @click="open = !open" type="button"
                            class="text-[11px] font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none 
                            {{ count($currentServiceStatuses) > 0 ? 'text-primary-600 hover:text-primary-700 underline decoration-primary-500/30 underline-offset-4' : 'text-gray-400 cursor-not-allowed' }}"
                            {{ count($currentServiceStatuses) == 0 ? 'disabled' : '' }}>
                            {{ count($currentServiceStatuses) > 0 ? 'View Details' : 'No Data' }}
                        </button>
                    </td>
                </tr>
                {{-- Status detail sub-row --}}
                @if (count($currentServiceStatuses) > 0)
                    <template x-if="open">
                        <tr class="bg-gray-50/50 dark:bg-white/5 border-b border-gray-100 dark:border-white/5">
                            <td colspan="6" class="px-6 py-6 bg-gray-50/80 dark:bg-gray-800/40">
                                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 overflow-hidden">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="w-1.5 h-6 bg-primary-500 rounded-full"></div>
                                            <h4 class="text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-200">
                                                Status Distribution
                                            </h4>
                                        </div>
                                        <div class="px-2.5 py-1 rounded-md text-[10px] bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-black border border-primary-100 dark:border-primary-800/50 uppercase">
                                            {{ $item->service?->service ?? 'Service #' . $item->id_service }}
                                        </div>
                                    </div>
                                    
                                    <div class="p-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                            @foreach (collect($currentServiceStatuses)->sortByDesc('total_status') as $st)
                                                <div class="flex flex-col p-3 rounded-lg border border-gray-100 dark:border-white/10 bg-white dark:bg-gray-800 shadow-sm hover:border-primary-200 dark:hover:border-primary-800/50 transition-colors duration-200">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Code</span>
                                                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total</span>
                                                    </div>
                                                    <div class="flex justify-between items-end">
                                                        <span class="text-base font-black text-gray-900 dark:text-white">
                                                            {{ $st->status !== null ? $st->status : 'N/A' }}
                                                        </span>
                                                        <div class="flex flex-col items-end">
                                                            <span class="text-base font-black text-primary-600 dark:text-primary-400 leading-tight">
                                                                {{ number_format((float) $st->total_status, 0, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                @endif
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                        <p class="font-medium text-sm">No service drops found for the selected period.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
