import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface Operator {
    id: number;
    operator_name: string;
    operator_code: string;
    status: string;
    description: string;
    country?: {
        id: number;
        country: string;
    };
}

interface Props {
    operator: Operator;
}

export default function Show({ operator }: Props) {
    return (
        <AppLayout>
            <Head title={`Operator Details - ${operator.operator_name}`} />

            <div className="mb-8 border-4 border-black bg-teal-500 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">{operator.operator_name}</h1>
                        <p className="mt-2 text-teal-100 font-bold font-mono">
                            // OPERATOR DETAILS
                        </p>
                    </div>
                    <div>
                        <Link
                            href={route('v2.operators.index')}
                            className="px-6 py-3 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase"
                        >
                            Back to List
                        </Link>
                    </div>
                </div>
            </div>

            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="p-6 border-b-4 border-black bg-yellow-400">
                    <h2 className="text-xl font-black text-black uppercase tracking-wider">
                        General Information
                    </h2>
                </div>
                <div className="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Operator Name
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white uppercase">
                            {operator.operator_name}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Operator Code
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white font-mono">
                            {operator.operator_code}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Country
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white uppercase">
                            {operator.country?.country || '-'}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Status
                        </label>
                        <div>
                            <span className={`inline-block px-3 py-1 border-2 border-black text-xs font-bold uppercase shadow-[2px_2px_0_0_rgba(0,0,0,1)] ${operator.status === 'active'
                                ? 'bg-green-400 text-black'
                                : 'bg-red-400 text-black'
                                }`}>
                                {operator.status}
                            </span>
                        </div>
                    </div>
                    <div className="md:col-span-2 p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Description
                        </label>
                        <div className="text-base font-bold text-black dark:text-white">
                            {operator.description || '-'}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
