import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FilterBar from '@/Components/FilterBar';
import Pagination from '@/Components/Pagination';

interface Country {
    id: number;
    country: string;
    country_code: string;
    currency: string;
    convert_usd: string;
    chat_id: string;
    thread_id_alert: string;
    thread_id_renewal: string;
}

interface Props {
    countries: {
        data: Country[];
        links: any[];
    };
}

export default function Index({ countries }: Props) {
    const handleExport = () => {
        const params = new URLSearchParams(window.location.search);
        window.location.href = `/v2/countries/export?${params.toString()}`;
    };

    return (
        <AppLayout>
            <Head title="Countries" />

            {/* Header / Stats Section */}
            <div className="mb-8 border-4 border-black bg-emerald-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Countries</h1>
                        <p className="mt-2 text-emerald-100 font-bold font-mono">
                            // MANAGE REGIONS & CURRENCIES
                        </p>
                    </div>
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-sm font-bold text-emerald-100 uppercase">Total Countries</p>
                            <p className="text-4xl font-black">{countries.data.length}</p>
                        </div>
                        <button className="px-6 py-3 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase">
                            Add Country
                        </button>
                    </div>
                </div>
            </div>

            <FilterBar
                placeholder="Search country..."
                enableExport={true}
                onExport={handleExport}
            />

            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-yellow-400 border-b-4 border-black text-xs uppercase tracking-wider text-black font-black">
                                <th className="px-6 py-4 border-r-2 border-black">Country</th>
                                <th className="px-6 py-4 border-r-2 border-black">Code</th>
                                <th className="px-6 py-4 border-r-2 border-black">Currency</th>
                                <th className="px-6 py-4 border-r-2 border-black">Rate /USD</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y-2 divide-black">
                            {countries.data.map((country) => (
                                <tr
                                    key={country.id}
                                    className="hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition-colors duration-150 font-bold"
                                >
                                    <td className="px-6 py-4 border-r-2 border-black">
                                        <div className="text-black dark:text-white uppercase">
                                            {country.country}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {country.country_code}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {country.currency}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {country.convert_usd}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm">
                                        <Link
                                            href={route('v2.countries.show', country.id)}
                                            className="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 font-bold border-2 border-black hover:bg-emerald-200 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-xs uppercase"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {countries.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-bold italic">
                                        NO COUNTRIES FOUND.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="p-4 border-t-4 border-black bg-gray-50 dark:bg-zinc-800">
                    <Pagination links={countries.links} />
                </div>
            </div>
        </AppLayout>
    );
}
