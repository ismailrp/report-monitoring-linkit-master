import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    roles: Role[];
    permissions: Permission[];
}

export default function Index({ roles, permissions }: Props) {
    const [isCreateRoleOpen, setIsCreateRoleOpen] = useState(false);
    const [roleName, setRoleName] = useState('');
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);

    const [editingRole, setEditingRole] = useState<Role | null>(null);

    const handleCreateRole = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(route('roles.store'), {
            name: roleName,
            permissions: selectedPermissions
        }, {
            onSuccess: () => {
                setIsCreateRoleOpen(false);
                setRoleName('');
                setSelectedPermissions([]);
            }
        });
    };

    const handleUpdateRole = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingRole) return;

        router.put(route('roles.update', editingRole.id), {
            name: roleName,
            permissions: selectedPermissions
        }, {
            onSuccess: () => {
                setEditingRole(null);
                setRoleName('');
                setSelectedPermissions([]);
            }
        });
    };

    const handleDeleteRole = (id: number) => {
        if (confirm('Are you sure you want to delete this role?')) {
            router.delete(route('roles.destroy', id));
        }
    };

    const openEditModal = (role: Role) => {
        setEditingRole(role);
        setRoleName(role.name);
        setSelectedPermissions(role.permissions.map(p => p.name));
    };

    const togglePermission = (name: string) => {
        if (selectedPermissions.includes(name)) {
            setSelectedPermissions(selectedPermissions.filter(p => p !== name));
        } else {
            setSelectedPermissions([...selectedPermissions, name]);
        }
    };

    const groupedPermissions = permissions.reduce((acc, permission) => {
        const parts = permission.name.split('_');
        const group = parts.length > 1 ? parts.slice(1).join('_') : 'Other';
        if (!acc[group]) acc[group] = [];
        acc[group].push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);

    return (
        <AppLayout>
            <Head title="Roles & Permissions" />

            <div className="mb-8 border-4 border-black bg-blue-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Roles & Permissions</h1>
                        <p className="mt-2 text-blue-100 font-bold font-mono">
                            // MANAGE ACCESS CONTROL AND USER ROLES, BY GROUP
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Roles List */}
                <div className="lg:col-span-2">
                    <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                        <div className="p-4 border-b-4 border-black flex justify-between items-center bg-yellow-400">
                            <h2 className="text-xl font-black text-black uppercase tracking-wider">ROLES LIST</h2>
                            <button
                                onClick={() => { setIsCreateRoleOpen(true); setEditingRole(null); setRoleName(''); setSelectedPermissions([]); }}
                                className="px-4 py-2 bg-black text-white text-sm font-bold border-2 border-transparent hover:bg-white hover:text-black hover:border-black transition-all shadow-[4px_4px_0_0_rgba(255,255,255,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:translate-x-[2px] active:translate-y-[2px] active:shadow-none uppercase"
                            >
                                + New Role
                            </button>
                        </div>
                        <div className="divide-y-4 divide-black">
                            {roles.map(role => (
                                <div key={role.id} className="p-6 hover:bg-yellow-50 dark:hover:bg-zinc-700/50 transition">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <h3 className="font-black text-2xl text-black dark:text-white uppercase mb-4">{role.name}</h3>
                                            <div className="space-y-2">
                                                {/* Group permissions in the view as well if needed, or just list them. 
                                                     For now keeping the list but maybe we can group them here too? 
                                                     Let's keeping it simple listed but nicely formatted. 
                                                     Actually, let's group them by model here too for consistency! 
                                                 */}
                                                {Object.entries(role.permissions.reduce((acc, p) => {
                                                    const parts = p.name.split('_');
                                                    const g = parts.length > 1 ? parts.slice(1).join('_') : 'Other';
                                                    if (!acc[g]) acc[g] = [];
                                                    acc[g].push(p);
                                                    return acc;
                                                }, {} as Record<string, Permission[]>)).map(([group, perms]) => (
                                                    <div key={group} className="flex items-start gap-2">
                                                        <span className="text-xs font-black uppercase text-gray-500 w-24 pt-1">{group}:</span>
                                                        <div className="flex flex-wrap gap-2 flex-1">
                                                            {perms.map(p => (
                                                                <span key={p.id} className="px-2 py-0.5 border-2 border-black bg-white text-black text-xs font-bold shadow-[2px_2px_0_0_rgba(0,0,0,1)] uppercase">
                                                                    {p.name.split('_')[0]}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}

                                                {role.permissions.length === 0 && <span className="text-sm text-gray-500 font-bold italic border-2 border-gray-300 p-1">NO PERMISSIONS ASSIGNED</span>}
                                            </div>
                                        </div>
                                        <div className="flex gap-3">
                                            <button
                                                onClick={() => openEditModal(role)}
                                                className="px-3 py-1 bg-blue-100 text-blue-800 font-bold border-2 border-black hover:bg-blue-200 shadow-[3px_3px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-sm uppercase"
                                            >
                                                EDIT
                                            </button>
                                            <button
                                                onClick={() => handleDeleteRole(role.id)}
                                                className="px-3 py-1 bg-red-100 text-red-800 font-bold border-2 border-black hover:bg-red-200 shadow-[3px_3px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[1px] active:translate-y-[1px] text-sm uppercase"
                                            >
                                                DELETE
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Permissions Reference */}
                <div className="lg:col-span-1">
                    <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6">
                        <h2 className="text-xl font-black text-black dark:text-white mb-6 uppercase border-b-4 border-black pb-2">Available Permissions</h2>
                        <div className="space-y-4">
                            {Object.entries(groupedPermissions).map(([group, perms]) => (
                                <div key={group}>
                                    <h3 className="font-black text-sm uppercase text-gray-500 mb-2 border-b-2 border-gray-200">{group}</h3>
                                    <div className="flex flex-wrap gap-2">
                                        {perms.map(p => (
                                            <span key={p.id} className="px-2 py-1 bg-gray-200 text-black border-2 border-black text-xs font-bold uppercase">
                                                {p.name.split('_')[0]}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal for Create/Edit Role */}
            {(isCreateRoleOpen || editingRole) && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
                    <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[12px_12px_0_0_rgba(255,255,0,1)] w-full max-w-2xl overflow-hidden relative">
                        {/* Close Button */}
                        <button
                            onClick={() => { setIsCreateRoleOpen(false); setEditingRole(null); }}
                            className="absolute top-4 right-4 text-black hover:bg-red-500 hover:text-white border-2 border-black p-1 transition-all"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={4} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div className="p-6 border-b-4 border-black bg-yellow-400">
                            <h3 className="text-3xl font-black text-black uppercase tracking-wider">
                                {editingRole ? 'Edit Role' : 'Create Syntax'}
                            </h3>
                        </div>
                        <form onSubmit={editingRole ? handleUpdateRole : handleCreateRole}>
                            <div className="p-8 space-y-6 max-h-[70vh] overflow-y-auto bg-white dark:bg-zinc-900">
                                <div>
                                    <label className="block text-sm font-black text-black dark:text-white mb-2 uppercase tracking-wide">Role Identifier</label>
                                    <input
                                        type="text"
                                        value={roleName}
                                        onChange={(e) => setRoleName(e.target.value)}
                                        className="w-full border-4 border-black p-3 font-bold text-lg focus:outline-none focus:shadow-[6px_6px_0_0_rgba(0,0,0,1)] transition-all bg-gray-50 dark:bg-zinc-800 dark:text-white dark:border-gray-500"
                                        placeholder="e.g. SUPER_ADMIN"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-black text-black dark:text-white mb-3 uppercase tracking-wide">Assign Permissions</label>
                                    <div className="space-y-4">
                                        {Object.entries(groupedPermissions).map(([group, perms]) => (
                                            <div key={group} className="border-2 border-dashed border-gray-300 dark:border-gray-600 p-4 rounded-none">
                                                <h4 className="font-black text-sm uppercase text-gray-500 mb-3">{group}</h4>
                                                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                    {perms.map(p => (
                                                        <label key={p.id} className={`flex items-center space-x-3 p-2 border-2 cursor-pointer transition-all ${selectedPermissions.includes(p.name) ? 'bg-black text-white border-black shadow-[4px_4px_0_0_rgba(100,100,100,1)]' : 'border-gray-300 hover:border-black hover:bg-gray-50 dark:border-gray-600'}`}>
                                                            <input
                                                                type="checkbox"
                                                                checked={selectedPermissions.includes(p.name)}
                                                                onChange={() => togglePermission(p.name)}
                                                                className="sr-only"
                                                            />
                                                            <div className={`w-4 h-4 border-2 border-white ${selectedPermissions.includes(p.name) ? 'bg-yellow-400' : 'bg-transparent'}`}></div>
                                                            <span className="text-xs font-bold uppercase">{p.name.split('_')[0]}</span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                            <div className="p-6 border-t-4 border-black bg-gray-100 dark:bg-zinc-800 flex justify-end gap-4">
                                <button
                                    type="button"
                                    onClick={() => { setIsCreateRoleOpen(false); setEditingRole(null); }}
                                    className="px-6 py-3 text-sm font-black text-black bg-white border-2 border-black hover:bg-gray-200 hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all uppercase tracking-wide"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-6 py-3 text-sm font-black text-white bg-blue-600 border-2 border-black hover:bg-blue-700 hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all uppercase tracking-wide"
                                >
                                    {editingRole ? 'Save Changes' : 'Create Role'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

        </AppLayout>
    );
}
