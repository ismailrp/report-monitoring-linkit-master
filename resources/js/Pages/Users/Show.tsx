import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string }[];
    created_at: string;
}

interface Props {
    user: User;
}

export default function Show({ user }: Props) {
    return (
        <AppLayout>
            <Head title={`User Details - ${user.name}`} />

            <div className="mb-8 border-4 border-black bg-purple-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">{user.name}</h1>
                        <p className="mt-2 text-purple-100 font-bold font-mono">
                            // USER DETAILS
                        </p>
                    </div>
                    <div>
                        <Link
                            href={route('v2.users.index')}
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
                            Name
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white uppercase">
                            {user.name}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Email
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white">
                            {user.email}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Roles
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {user.roles && user.roles.length > 0 ? (
                                user.roles.map((role, index) => (
                                    <span key={index} className="px-2 py-1 border-2 border-black bg-white text-black text-xs font-bold shadow-[2px_2px_0_0_rgba(0,0,0,1)] uppercase">
                                        {role.name}
                                    </span>
                                ))
                            ) : (
                                <span className="text-gray-400 italic font-bold">NO ROLES</span>
                            )}
                        </div>
                    </div>
                    <div className="p-4 border-2 border-black bg-gray-50 dark:bg-zinc-900 shadow-[4px_4px_0_0_rgba(0,0,0,1)]">
                        <label className="block text-xs font-black text-gray-500 uppercase tracking-wide mb-2">
                            Joined At
                        </label>
                        <div className="text-xl font-bold text-black dark:text-white font-mono">
                            {new Date(user.created_at).toLocaleDateString()}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
