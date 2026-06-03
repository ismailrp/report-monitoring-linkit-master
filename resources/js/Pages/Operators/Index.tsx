import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FilterBar from '@/Components/FilterBar';
import Pagination from '@/Components/Pagination';

interface Operator {
    id: number;
    operator: string;
    status: string;
    country?: {
        id: number;
        country: string;
    };
}

interface Props {
    operators: {
        data: Operator[];
        links: any[];
    };
}

export default function Index({ operators }: Props) {
    const handleExport = () => {
        const params = new URLSearchParams(window.location.search);
        window.location.href = `/v2/operators/export?${params.toString()}`;
    };

    return (
        <AppLayout>
            <Head title="Operators" />

            {/* Header / Stats Section */}
            <div className="mb-8 border-4 border-black bg-teal-500 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Operators</h1>
                        <p className="mt-2 text-teal-100 font-bold font-mono">
                            // MANAGE TELECOM OPERATORS
                        </p>
                    </div>
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-sm font-bold text-teal-100 uppercase">Total Operators</p>
                            <p className="text-4xl font-black">{operators.data.length}</p>
                        </div>
                        <button className="px-6 py-3 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase">
                            Add Operator
                        </button>
                    </div>
                </div>
            </div>

            <FilterBar
                placeholder="Search operator..."
                enableExport={true}
                onExport={handleExport}
            />

            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-yellow-400 border-b-4 border-black text-xs uppercase tracking-wider text-black font-black">
                                <th className="px-6 py-4 border-r-2 border-black">Operator Name</th>
                                <th className="px-6 py-4 border-r-2 border-black">Country</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y-2 divide-black">
                            {operators.data.map((operator) => (
                                <tr
                                    key={operator.id}
                                    className="hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition-colors duration-150 font-bold"
                                >
                                    <td className="px-6 py-4 border-r-2 border-black">
                                        <div className="text-black dark:text-white uppercase">
                                            {operator.operator}
                                        </div>
                                    </td>

                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white uppercase">
                                        {operator.country?.country || '-'}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm">
                                        <Link
                                            href={route('v2.operators.show', operator.id)}
                                            className="inline-block px-3 py-1 bg-teal-100 text-teal-800 font-bold border-2 border-black hover:bg-teal-200 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-xs uppercase"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {operators.data.length === 0 && (
                                <tr>
                                    <td colSpan={3} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-bold italic">
                                        NO OPERATORS FOUND.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {/* Pagination */}
                <div className="p-4 border-t-4 border-black bg-gray-50 dark:bg-zinc-800">
                    <Pagination links={operators.links} />
                </div>
            </div>
        </AppLayout>
    );
}
