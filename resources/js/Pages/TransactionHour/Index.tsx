import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FilterBar from '@/Components/FilterBar';
import Pagination from '@/Components/Pagination';

interface Item {
    id: number;
    date: string;
    hour: number;
    country?: string;
    operator?: {
        id: number;
        operator: string;
    };
    service?: {
        id: number;
        service: string;
    };
    mt_success: number;
    mt_failed: number;
    total_mt: number;
    revenue: number;
    [key: string]: any;
}

interface Props {
    data: {
        data: Item[];
        links: any[];
    };
    countries: { id: number; country: string }[];
    operators: { id: number; operator: string }[];
    services: { id: number; service: string }[];
}

export default function Index({ data, countries, operators, services }: Props) {
    const handleExport = () => {
        const params = new URLSearchParams(window.location.search);
        window.location.href = `/v2/transaction-hours/export?${params.toString()}`;
    };

    return (
        <AppLayout>
            <Head title="Transaction Hour" />

            <div className="mb-8 border-4 border-black bg-pink-500 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Transaction Hourly</h1>
                        <p className="mt-2 text-pink-100 font-bold font-mono">
                            // HOURLY TRANSACTION & REVENUE
                        </p>
                    </div>
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-sm font-bold text-pink-100 uppercase">Total Records</p>
                            <p className="text-4xl font-black">{data.data.length}</p>
                        </div>
                    </div>
                </div>
            </div>

            <FilterBar
                placeholder="Search..."
                enableDateRange={true}
                enableExport={true}
                onExport={handleExport}
                countries={countries}
                operators={operators}
                services={services}
            />

            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-yellow-400 border-b-4 border-black text-xs uppercase tracking-wider text-black font-black">
                                <th className="px-6 py-4 border-r-2 border-black">Date</th>
                                <th className="px-6 py-4 border-r-2 border-black">Hour</th>
                                <th className="px-6 py-4 border-r-2 border-black">Country</th>
                                <th className="px-6 py-4 border-r-2 border-black">Operator</th>
                                <th className="px-6 py-4 border-r-2 border-black">Service</th>
                                <th className="px-6 py-4 border-r-2 border-black text-right">MT Success</th>
                                <th className="px-6 py-4 border-r-2 border-black text-right">MT Failed</th>
                                <th className="px-6 py-4 border-r-2 border-black text-right">Total MT</th>
                                <th className="px-6 py-4 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y-2 divide-black">
                            {data.data.map((item) => (
                                <tr
                                    key={item.id}
                                    className="hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition-colors duration-150 font-bold"
                                >
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white whitespace-nowrap">
                                        {item.date}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {item.hour}:00
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white uppercase">
                                        {item.country || '-'}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white">
                                        {item.operator?.operator || '-'}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white">
                                        {item.service?.service || '-'}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white text-right font-mono">
                                        {item.mt_success?.toLocaleString() || '0'}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white text-right font-mono">
                                        {item.mt_failed?.toLocaleString() || '0'}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white text-right font-mono">
                                        {item.total_mt?.toLocaleString() || '0'}
                                    </td>
                                    <td className="px-6 py-4 text-sm text-black dark:text-white text-right font-mono font-bold">
                                        ${item.revenue?.toLocaleString() || '0'}
                                    </td>
                                </tr>
                            ))}
                            {data.data.length === 0 && (
                                <tr>
                                    <td colSpan={9} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-bold italic">
                                        NO DATA FOUND FOR THIS PERIOD.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="p-4 border-t-4 border-black bg-gray-50 dark:bg-zinc-800">
                    <Pagination links={data.links} />
                </div>
            </div>
        </AppLayout>
    );
}
