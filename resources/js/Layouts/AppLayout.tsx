import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    Building2,
    Globe,
    Bell,
    Store,
    Headset,
    Server,
    FileText,
    Calendar,
    Smartphone,
    Activity,
    UserCheck,
    CreditCard,
    Menu,
    LogOut,
    User,
    ChevronDown,
    Moon,
    Sun
} from 'lucide-react';

interface Props {
    children: React.ReactNode;
}

export default function AppLayout({ children }: Props) {
    const { url, props } = usePage();
    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false);
    const [theme, setTheme] = useState('light');

    // Initialize Theme
    React.useEffect(() => {
        const savedTheme = localStorage.getItem('theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const initialTheme = savedTheme || systemTheme;

        setTheme(initialTheme);
        if (initialTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }, []);

    const toggleTheme = () => {
        const newTheme = theme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        localStorage.setItem('theme', newTheme);

        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    // Get permissions from props
    const permissions = (props.auth as any).permissions || [];
    const user = (props.auth as any).user;

    const hasPermission = (permission: string) => {
        if (!permission) return true; // No permission required
        return permissions.includes(permission);
    }

    // Grouping based on Filament structure with Permissions
    const navigation = [
        {
            group: 'Dashboard',
            items: [
                { name: 'Dashboard', href: '/v2/dashboard', icon: LayoutDashboard, active: url.startsWith('/v2/dashboard'), permission: 'view_dashboard' },
            ]
        },
        {
            group: 'Master Data',
            items: [
                { name: 'Users', href: '/v2/users', icon: Users, active: url.startsWith('/v2/users'), permission: 'view_any_user' },
                { name: 'Companies', href: '/v2/companies', icon: Building2, active: url.startsWith('/v2/companies'), permission: 'view_any_company' },
                { name: 'Countries', href: '/v2/countries', icon: Globe, active: url.startsWith('/v2/countries'), permission: 'view_any_country' },
                { name: 'Merchants', href: '/v2/merchants', icon: Store, active: url.startsWith('/v2/merchants'), permission: 'view_any_merchant' },
                { name: 'Operators', href: '/v2/operators', icon: Headset, active: url.startsWith('/v2/operators'), permission: 'view_any_operator' },
                { name: 'Services', href: '/v2/services', icon: Server, active: url.startsWith('/v2/services'), permission: 'view_any_service' },
                { name: 'Alerts', href: '/v2/alerts', icon: Bell, active: url.startsWith('/v2/alerts'), permission: 'view_any_alert' },
                { name: 'Roles & Permissions', href: '/v2/roles-permissions', icon: UserCheck, active: url.startsWith('/v2/roles-permissions'), permission: 'view_any_role' },
            ]
        },
        {
            group: 'Reports',
            items: [
                { name: 'Summary Daily', href: '/v2/summary-daily', icon: FileText, active: url.startsWith('/v2/summary-daily'), permission: 'view_any_summary_daily' },
                { name: 'Summary Weekly', href: '/v2/summary-weekly', icon: Calendar, active: url.startsWith('/v2/summary-weekly'), permission: 'view_any_summary_weekly' },
                { name: 'MO Hours', href: '/v2/mo-hours', icon: Smartphone, active: url.startsWith('/v2/mo-hours'), permission: 'view_any_mo_hour' },
                { name: 'SR Hours', href: '/v2/sr-hours', icon: Activity, active: url.startsWith('/v2/sr-hours'), permission: 'view_any_sr_hour' },
                { name: 'Sub Active Users', href: '/v2/sub-active-user-hours', icon: UserCheck, active: url.startsWith('/v2/sub-active-user-hours'), permission: 'view_any_sub_active_user' },
                { name: 'Transaction Hours', href: '/v2/transaction-hours', icon: CreditCard, active: url.startsWith('/v2/transaction-hours'), permission: 'view_any_transaction_hour' },
            ]
        }
    ];

    return (
        <div className="min-h-screen bg-yellow-50 dark:bg-zinc-900 flex font-mono border-x-4 border-black">
            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-zinc-800 border-r-4 border-black transform transition-transform duration-300 ease-in-out ${isMobileOpen ? 'translate-x-0' : '-translate-x-full'
                    } lg:translate-x-0 lg:static lg:block`}
            >
                <div className="h-16 flex items-center justify-center border-b-4 border-black bg-yellow-300 dark:bg-yellow-600">
                    <span className="text-xl font-black text-black">
                        ADMIN PANEL
                    </span>
                </div>

                <nav className="p-4 space-y-6 overflow-y-auto h-[calc(100vh-4rem)]">
                    {navigation.map((group, groupIdx) => {
                        // Filter items based on permission
                        const visibleItems = group.items.filter(item => hasPermission(item.permission));

                        // For testing/development if permissions user property is missing
                        // const visibleItems = group.items; 

                        if (visibleItems.length === 0) return null;

                        return (
                            <div key={groupIdx}>
                                {group.group !== 'Dashboard' && (
                                    <h3 className="px-3 text-xs font-black text-black dark:text-gray-400 uppercase tracking-widest mb-2 border-b-2 border-black inline-block">
                                        {group.group}
                                    </h3>
                                )}
                                <div className="space-y-2">
                                    {visibleItems.map((item) => {
                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`flex items-center px-3 py-2 text-sm font-bold border-2 transition-all duration-150 ${item.active
                                                    ? 'bg-black text-white border-black shadow-[4px_4px_0px_0px_rgba(100,100,100,1)]'
                                                    : 'text-black dark:text-gray-200 border-transparent hover:border-black hover:bg-yellow-200 hover:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]'
                                                    }`}
                                            >
                                                <Icon className={`w-5 h-5 mr-3 ${item.active ? 'text-yellow-400' : 'text-black dark:text-gray-400'}`} />
                                                {item.name}
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </nav>
            </aside>

            {/* Mobile Overlay */}
            {isMobileOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                    onClick={() => setIsMobileOpen(false)}
                />
            )}

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden bg-white dark:bg-zinc-900">
                <header className="bg-white dark:bg-black border-b-4 border-black z-30">
                    <div className="flex items-center justify-between h-16 px-4">
                        <button
                            onClick={() => setIsMobileOpen(!isMobileOpen)}
                            className="lg:hidden p-2 text-black hover:bg-gray-100 border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px]"
                        >
                            <Menu className="h-6 w-6" />
                        </button>

                        <div className="flex ml-auto items-center gap-4">
                            {/* Theme Toggle Button */}
                            <button
                                onClick={toggleTheme}
                                className="p-2 text-black dark:text-white border-2 border-black bg-white dark:bg-zinc-800 hover:bg-gray-100 dark:hover:bg-zinc-700 shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[1px] hover:translate-y-[1px] transition-all"
                                title="Toggle Theme"
                            >
                                {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
                            </button>

                            {/* User Dropdown */}
                            <div className="relative">
                                <button
                                    onClick={() => setIsUserDropdownOpen(!isUserDropdownOpen)}
                                    className="flex items-center gap-2 px-4 py-2 text-sm font-bold text-black border-2 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] transition-all bg-white"
                                >
                                    <span>{user?.name || 'Admin'}</span>
                                    <ChevronDown className="w-4 h-4" />
                                </button>

                                {isUserDropdownOpen && (
                                    <>
                                        <div
                                            className="fixed inset-0 z-10"
                                            onClick={() => setIsUserDropdownOpen(false)}
                                        />
                                        <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 border-2 border-black shadow-[6px_6px_0_0_rgba(0,0,0,1)] py-0 z-20">
                                            <div className="px-4 py-3 border-b-2 border-black bg-yellow-100">
                                                <p className="text-sm font-bold text-black truncate">{user?.email}</p>
                                            </div>
                                            <Link
                                                href={route('v2.profile.edit')}
                                                className="flex items-center px-4 py-3 text-sm font-bold text-black hover:bg-blue-200 border-b-2 border-black"
                                                onClick={() => setIsUserDropdownOpen(false)}
                                            >
                                                <User className="w-4 h-4 mr-2" />
                                                Profile
                                            </Link>
                                            <Link
                                                href={route('v2.logout')}
                                                method="post"
                                                as="button"
                                                className="flex w-full items-center px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-100"
                                            >
                                                <LogOut className="w-4 h-4 mr-2" />
                                                Log Out
                                            </Link>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-zinc-900">
                    {children}
                </main>
            </div>
        </div>
    );
}
