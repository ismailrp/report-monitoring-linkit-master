import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface Service {
    id: number;
    service_name: string;
    service_code: string;
    Operator?: {
        id: number;
        operator_name: string;
    };
    [key: string]: any;
}

interface Props {
    service: Service;
}

export default function Show({ service }: Props) {
    return (
        <AppLayout>
            <Head title={`Service Details - ${service.service_name}`} />

            <div className="mb-8 border-4 border-black bg-blue-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">{service.service_name}</h1>
                        <p className="mt-2 text-blue-100 font-bold font-mono">
                            // SERVICE DETAILS
                        </p>
                    </div>
                    <div>
                        <Link
                            href={route('v2.services.index')}
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
                            Service Name
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white uppercase">
                            {service.service_name}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Service Code
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white font-mono">
                            {service.service_code}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Operator
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white uppercase">
                            {service.Operator?.operator_name || '-'}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
