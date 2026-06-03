<div class="p-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Operator</th>
                    <th class="px-4 py-2">Service</th>
                    <th class="px-4 py-2">MT Success</th>
                    <th class="px-4 py-2">MT Failed</th>
                    <th class="px-4 py-2">Reg</th>
                    <th class="px-4 py-2">Unreg</th>
                    <th class="px-4 py-2">Revenue</th>
                    <th class="px-4 py-2">Revenue USD</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $item)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-2">{{ $item->date->format('Y-m-d') }}</td>
                        <td class="px-4 py-2">{{ $item->operator }}</td>
                        <td class="px-4 py-2">{{ $item->service }}</td>
                        <td class="px-4 py-2 font-mono">{{ number_format($item->mt_success) }}</td>
                        <td class="px-4 py-2 font-mono">{{ number_format($item->mt_failed) }}</td>
                        <td class="px-4 py-2 font-mono">{{ number_format($item->reg) }}</td>
                        <td class="px-4 py-2 font-mono">{{ number_format($item->unreg) }}</td>
                        <td class="px-4 py-2 font-mono">{{ number_format($item->revenue, 2) }}</td>
                        <td class="px-4 py-2 font-mono text-success-600 dark:text-success-400">
                            ${{ number_format($item->revenue_usd, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
