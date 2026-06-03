import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface Alert {
    id: number;
    // Add specific fields if known, otherwise generic
    [key: string]: any;
}

interface Props {
    alerts: {
        data: Alert[];
        links: any[];
    };
}

export default function Index({ alerts }: Props) {
    return (
        <AppLayout>
            <Head title="Alerts" />

            <div className="mb-8 rounded-2xl bg-gradient-to-r from-red-600 to-orange-500 p-6 text-white shadow-lg">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold">Alerts</h1>
                        <p className="mt-1 text-red-100 opacity-90">
                            Monitor system alerts.
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-right">
                            <p className="text-sm font-medium text-red-100">Total Alerts</p>
                            <p className="text-3xl font-bold">{alerts.data.length}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div className="p-6 text-center text-gray-500 dark:text-gray-400">
                    {/* Placeholder table since model structure is generic */}
                    <p>Alert data table will appear here.</p>
                </div>
            </div>
        </AppLayout>
    );
}
