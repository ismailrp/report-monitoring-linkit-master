import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';

interface Company {
    id: number;
    company: string;
    chat_id: string;
    thread_id_renewal: string;
    thread_id_alert: string;
    created_at: string;
}

interface Props {
    companies: {
        data: Company[];
        links: any[];
    };
}

export default function Index({ companies }: Props) {
    const handleExport = () => {
        const params = new URLSearchParams(window.location.search);
        window.location.href = `/v2/companies/export?${params.toString()}`;
    };

    return (
        <AppLayout>
            <Head title="Companies" />

            {/* Header / Stats Section */}
            <div className="mb-8 border-4 border-black bg-indigo-500 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Companies</h1>
                        <p className="mt-2 text-indigo-100 font-bold font-mono">
                            // MANAGE PARTNER COMPANIES
                        </p>
                    </div>
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-sm font-bold text-indigo-100 uppercase">Total Companies</p>
                            <p className="text-4xl font-black">{companies.data.length}</p>
                        </div>
                        <button className="px-6 py-3 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase">
                            Add Company
                        </button>
                    </div>
                </div>
            </div>

            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-yellow-400 border-b-4 border-black text-xs uppercase tracking-wider text-black font-black">
                                <th className="px-6 py-4 border-r-2 border-black">Company Name</th>
                                <th className="px-6 py-4 border-r-2 border-black">Chat ID</th>
                                <th className="px-6 py-4 border-r-2 border-black">Renewal Thread</th>
                                <th className="px-6 py-4 border-r-2 border-black">Alert Thread</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y-2 divide-black">
                            {companies.data.map((company) => (
                                <tr
                                    key={company.id}
                                    className="hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition-colors duration-150 font-bold"
                                >
                                    <td className="px-6 py-4 border-r-2 border-black">
                                        <div className="text-black dark:text-white uppercase">
                                            {company.company}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {company.chat_id}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {company.thread_id_renewal}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm text-black dark:text-white font-mono">
                                        {company.thread_id_alert}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm font-medium">
                                        <button className="mr-2 inline-block px-3 py-1 bg-blue-100 text-blue-800 font-bold border-2 border-black hover:bg-blue-200 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-xs uppercase">
                                            Edit
                                        </button>
                                        <button className="inline-block px-3 py-1 bg-red-100 text-red-800 font-bold border-2 border-black hover:bg-red-200 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-xs uppercase">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {companies.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-bold italic">
                                        NO COMPANIES FOUND.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="p-4 border-t-4 border-black bg-gray-50 dark:bg-zinc-800">
                    <Pagination links={companies.links} />
                </div>
            </div>
        </AppLayout>
    );
}
