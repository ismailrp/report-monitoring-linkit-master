{{-- Operator-level Status Summary --}}
<div class="mb-5">
    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
        Status Summary &mdash; Operator: <span class="text-gray-700 dark:text-gray-200">{{ $operatorName ?? '-' }}</span>
        &nbsp;|&nbsp; Date: <span class="text-gray-700 dark:text-gray-200">{{ $date ?? '-' }}</span>
        &nbsp;|&nbsp; Hour: <span class="text-gray-700 dark:text-gray-200">{{ $hour ?? '-' }}</span>
        @if(isset($statusHour))
            &nbsp;|&nbsp; Status Data Hour: <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $statusHour }}</span>
        @endif
    </h3>

    @if (isset($statuses) && count($statuses) > 0)
        <div class="overflow-x-auto rounded-lg shadow ring-1 ring-black/5 dark:ring-white/10">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-2.5 font-medium text-right text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5 bg-white dark:bg-gray-900">
                    @foreach ($statuses as $s)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-900 dark:text-white font-medium">
                                {{ $s->status !== null ? $s->status : 'N/A' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-900 dark:text-white">
                                {{ number_format((float) $s->total_status, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-3 text-center bg-gray-50 dark:bg-white/5 rounded-lg border border-dashed border-gray-300 dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No status summary data available.</p>
        </div>
    @endif
</div>

{{-- Per-Service Drop List with per-service status breakdown --}}
<h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
    Service Drop List
</h3>
<div class="overflow-x-auto rounded-lg shadow ring-1 ring-black/5 dark:ring-white/10">
    <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-[11px] uppercase">#</th>
                <th class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-[11px] uppercase">Country</th>
                <th class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-[11px] uppercase">Service</th>
                <th class="px-4 py-2.5 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase">Today</th>
                <th class="px-4 py-2.5 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase">Yesterday</th>
                <th class="px-4 py-2.5 font-medium text-right text-gray-500 dark:text-gray-400 text-[11px] uppercase">Drop</th>
                <th class="px-4 py-2.5 font-medium text-center text-gray-500 dark:text-gray-400 text-[11px] uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
            @forelse($services as $index => $item)
                @php
                    $isRev = strtoupper($item->type) === 'REVENUE';
                    $rate = $isRev ? ($item->country?->convert_usd ?? 1) : 1;
                    if ($isRev && strtoupper($item->country?->country ?? '') === 'OMAN') {
                        $rate = $rate / 1000;
                    }
                    $todayVal = (float) $item->today * $rate;
                    $yesterdayVal = (float) $item->yesterday * $rate;
                    $dropPercent = $yesterdayVal > 0
                        ? (($yesterdayVal - $todayVal) / $yesterdayVal) * 100
                        : 0;

                    // Per-service statuses keyed by id_service
                    $svcStatuses = collect($serviceStatuses->get($item->id_service, []));
                @endphp
                <tr x-data="{ open: false }">
                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $item->country?->country ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $item->service?->service ?? 'Unknown' }}</td>
                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($todayVal, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($yesterdayVal, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $dropPercent > 10 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                        {{ number_format($dropPercent, 2) }}%
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button @click="open = !open" type="button"
                            class="text-[11px] font-bold uppercase tracking-wider transition-all duration-200 focus:outline-none 
                            {{ count($svcStatuses) > 0 ? 'text-primary-600 hover:text-primary-700 underline decoration-primary-500/30 underline-offset-4' : 'text-gray-400 cursor-not-allowed' }}"
                            {{ count($svcStatuses) == 0 ? 'disabled' : '' }}>
                            {{ count($svcStatuses) > 0 ? 'View Details' : 'No Data' }}
                        </button>
                    </td>
                </tr>
                @if (count($svcStatuses) > 0)
                    <template x-if="open">
                        <tr class="bg-gray-50/50 dark:bg-white/5 border-b border-gray-100 dark:border-white/5">
                            <td colspan="7" class="px-6 py-6 bg-gray-50/80 dark:bg-gray-800/40">
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
                                            @foreach (collect($svcStatuses)->sortByDesc('total') as $st)
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
                                                                {{ number_format((float) $st->total, 0, ',', '.') }}
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
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        No discontinued services found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
