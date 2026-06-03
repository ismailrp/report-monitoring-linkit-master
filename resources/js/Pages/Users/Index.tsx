import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';

interface User {
    id: number;
    name: string;
    email: string;
    roles: { name: string }[];
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        links: any[];
    };
}

export default function Index({ users }: Props) {
    return (
        <AppLayout>
            <Head title="Users" />

            {/* Header / Stats Section */}
            <div className="mb-8 border-4 border-black bg-purple-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">User Management</h1>
                        <p className="mt-2 text-purple-100 font-bold font-mono">
                            // MANAGE ACCESS AND ROLES
                        </p>
                    </div>
                    <div className="flex items-center gap-6">
                        <div className="text-right">
                            <p className="text-sm font-bold text-purple-100 uppercase">Total Users</p>
                            <p className="text-4xl font-black">{users.data.length}</p>
                        </div>
                        <button className="px-6 py-3 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase">
                            Add User
                        </button>
                    </div>
                </div>
            </div>

            {/* Clean Table Section */}
            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-yellow-400 border-b-4 border-black text-xs uppercase tracking-wider text-black font-black">
                                <th className="px-6 py-4 border-r-2 border-black">Name</th>
                                <th className="px-6 py-4 border-r-2 border-black">Email</th>
                                <th className="px-6 py-4 border-r-2 border-black">Role</th>
                                <th className="px-6 py-4 border-r-2 border-black">Joined</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y-2 divide-black">
                            {users.data.map((user) => (
                                <tr
                                    key={user.id}
                                    className="hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition-colors duration-150 font-bold"
                                >
                                    <td className="px-6 py-4 border-r-2 border-black">
                                        <div className="flex items-center">
                                            <div className="h-10 w-10 border-2 border-black bg-white flex items-center justify-center text-black font-black text-sm shadow-[2px_2px_0_0_rgba(0,0,0,1)]">
                                                {user.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="ml-4">
                                                <div className="font-bold text-black dark:text-white uppercase">
                                                    {user.name}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm">
                                        {user.email}
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black">
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles && user.roles.length > 0 ? (
                                                user.roles.map((role, index) => (
                                                    <span key={index} className="inline-flex items-center px-2 py-0.5 border-2 border-black text-xs font-bold bg-gray-100 text-black shadow-[2px_2px_0_0_rgba(0,0,0,1)] uppercase">
                                                        {role.name}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="text-gray-400 text-sm italic font-bold">NO ROLES</span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 border-r-2 border-black text-sm font-mono">
                                        {new Date(user.created_at).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm">
                                        <Link
                                            href={route('v2.users.show', user.id)}
                                            className="inline-block px-3 py-1 bg-purple-100 text-purple-800 font-bold border-2 border-black hover:bg-purple-200 shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-xs uppercase"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400 font-bold italic">
                                        NO USERS FOUND.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Footer / Pagination */}
                <div className="p-4 border-t-4 border-black bg-gray-50 dark:bg-zinc-800">
                    <Pagination links={users.links} />
                </div>
            </div>
        </AppLayout>
    );
}
