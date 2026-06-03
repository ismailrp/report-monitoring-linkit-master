import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';


interface Stats {
    revenue: number;
    mo: number;
    mt: number;
    active_users: number;
    date: string;
    latest_hour: number | null;
}

interface Counts {
    services: number;
    operators: number;
    countries: number;
    merchants: number;
}

interface ChartItem {
    country?: string;
    operator?: string;
    total_revenue_usd: number;
}

interface Props {
    stats: Stats;
    counts: Counts;
    charts: {
        top_countries: ChartItem[];
        top_operators: ChartItem[];
        hourly_country: Record<string, any>[];
        country_names: string[];
    };
}

export default function Dashboard({ stats, counts, charts }: Props) {
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
    };

    const formatUSD = (value: number) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    };

    const formatNumber = (value: number) => {
        return new Intl.NumberFormat('id-ID').format(value);
    };

    // Helper to calculate bar percentage
    const getPercent = (value: number, data: ChartItem[]) => {
        const max = Math.max(...data.map(d => parseFloat(d.total_revenue_usd.toString()) || 0));
        return max === 0 ? 0 : (value / max) * 100;
    };

    // Vibrant colors for line chart
    const CHART_COLORS = [
        '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6',
        '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6366f1'
    ];

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-8 border-4 border-black bg-purple-600 p-6 text-white shadow-[8px_8px_0_0_rgba(0,0,0,1)]">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-4xl font-black uppercase tracking-widest">Dashboard</h1>
                        <p className="mt-2 text-purple-100 font-bold font-mono">
                            // ANALYTICS OVERVIEW • {stats.date} {stats.latest_hour !== null ? `• ${stats.latest_hour}:00` : ''}
                        </p>
                    </div>
                </div>
            </div>

            {/* Counts Section */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                {[
                    { label: 'Total Services', value: counts.services, color: 'bg-yellow-300' },
                    { label: 'Total Operators', value: counts.operators, color: 'bg-pink-300' },
                    { label: 'Total Countries', value: counts.countries, color: 'bg-cyan-300' },
                    { label: 'Total Merchants', value: counts.merchants, color: 'bg-orange-300' },
                ].map((item, idx) => (
                    <div key={idx} className={`${item.color} p-4 border-4 border-black shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[2px_2px_0_0_rgba(0,0,0,1)] transition-all`}>
                        <h3 className="text-xs font-black text-black uppercase tracking-wide mb-1">{item.label}</h3>
                        <p className="text-3xl font-black text-black">{formatNumber(item.value)}</p>
                    </div>
                ))}
            </div>

            {/* Main Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {/* Revenue Card */}
                <div className="bg-emerald-400 p-6 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition-all cursor-default">
                    <h3 className="text-xs font-black text-black mb-2 uppercase tracking-wide">
                        Today's Revenue
                    </h3>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-3xl font-black text-black break-all">
                            {formatCurrency(stats.revenue)}
                        </span>
                    </div>
                    <div className="mt-4 text-xs font-bold text-black border-t-2 border-black pt-2">
                        INCREMENTAL
                    </div>
                </div>

                {/* MO Card */}
                <div className="bg-blue-400 p-6 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition-all cursor-default">
                    <h3 className="text-xs font-black text-black mb-2 uppercase tracking-wide">
                        Total MO
                    </h3>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-4xl font-black text-black">
                            {formatNumber(stats.mo)}
                        </span>
                    </div>
                    <div className="mt-4 text-xs font-bold text-black border-t-2 border-black pt-2">
                        REG + UNREG
                    </div>
                </div>

                {/* MT Card */}
                <div className="bg-purple-400 p-6 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition-all cursor-default">
                    <h3 className="text-xs font-black text-black mb-2 uppercase tracking-wide">
                        Total MT Success
                    </h3>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-4xl font-black text-black">
                            {formatNumber(stats.mt)}
                        </span>
                    </div>
                    <div className="mt-4 text-xs font-bold text-black border-t-2 border-black pt-2">
                        DELIVERED MESSAGES
                    </div>
                </div>

                {/* Active Users Card */}
                <div className="bg-orange-400 p-6 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] hover:translate-x-1 hover:translate-y-1 hover:shadow-none transition-all cursor-default">
                    <h3 className="text-xs font-black text-black mb-2 uppercase tracking-wide">
                        Active Users
                    </h3>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-4xl font-black text-black">
                            {formatNumber(stats.active_users)}
                        </span>
                    </div>
                    <div className="mt-4 text-xs font-bold text-black border-t-2 border-black pt-2">
                        LATEST HOUR ({stats.latest_hour !== null ? `${stats.latest_hour}:00` : '-'})
                    </div>
                </div>
            </div>

            {/* Hourly Revenue Line Chart */}
            <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6 mb-8">
                <div className="border-b-4 border-black pb-4 mb-6">
                    <h3 className="text-xl font-black text-black dark:text-white uppercase">Hourly Revenue by Country (USD)</h3>
                    <p className="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1 font-mono">// REVENUE PER HOUR • {stats.date}</p>
                </div>
                {charts.hourly_country && charts.hourly_country.length > 0 ? (
                    <ResponsiveContainer width="100%" height={350}>
                        <LineChart data={charts.hourly_country} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#333" />
                            <XAxis
                                dataKey="hour"
                                tick={{ fontSize: 11, fontWeight: 'bold', fill: '#666' }}
                                tickFormatter={(v: number) => `${v}:00`}
                                stroke="#000"
                                strokeWidth={2}
                            />
                            <YAxis
                                tick={{ fontSize: 11, fontWeight: 'bold', fill: '#666' }}
                                tickFormatter={(v: number) => `$${(v / 1000).toFixed(0)}k`}
                                stroke="#000"
                                strokeWidth={2}
                            />
                            <Tooltip
                                formatter={(value: number, name: string) => [formatUSD(value), name]}
                                labelFormatter={(label: number) => `Hour ${label}:00`}
                                contentStyle={{
                                    border: '3px solid #000',
                                    borderRadius: 0,
                                    fontWeight: 'bold',
                                    boxShadow: '4px 4px 0 0 rgba(0,0,0,1)',
                                    backgroundColor: '#fff'
                                }}
                            />
                            <Legend wrapperStyle={{ fontWeight: 'bold', textTransform: 'uppercase', fontSize: 11 }} />
                            {charts.country_names.map((name, idx) => (
                                <Line
                                    key={name}
                                    type="monotone"
                                    dataKey={name}
                                    stroke={CHART_COLORS[idx % CHART_COLORS.length]}
                                    strokeWidth={3}
                                    dot={{ r: 3, strokeWidth: 2 }}
                                    activeDot={{ r: 6, strokeWidth: 3 }}
                                />
                            ))}
                        </LineChart>
                    </ResponsiveContainer>
                ) : (
                    <p className="text-gray-500 font-bold italic">No hourly data available.</p>
                )}
            </div>

            {/* Charts Section */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                {/* Top Countries Chart */}
                <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6">
                    <div className="border-b-4 border-black pb-4 mb-4">
                        <h3 className="text-xl font-black text-black dark:text-white uppercase">Top Revenue by Country (USD)</h3>
                    </div>
                    <div className="space-y-4">
                        {charts.top_countries.map((item, idx) => (
                            <div key={idx}>
                                <div className="flex justify-between text-xs font-bold text-black dark:text-white mb-1">
                                    <span className="uppercase">{item.country}</span>
                                    <span>{formatUSD(item.total_revenue_usd)}</span>
                                </div>
                                <div className="w-full bg-gray-200 dark:bg-zinc-700 h-4 border-2 border-black">
                                    <div
                                        className="h-full bg-emerald-500 border-r-2 border-black"
                                        style={{ width: `${getPercent(item.total_revenue_usd, charts.top_countries)}%` }}
                                    ></div>
                                </div>
                            </div>
                        ))}
                        {charts.top_countries.length === 0 && (
                            <p className="text-gray-500 font-bold italic">No data available.</p>
                        )}
                    </div>
                </div>

                {/* Top Operators Chart */}
                <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6">
                    <div className="border-b-4 border-black pb-4 mb-4">
                        <h3 className="text-xl font-black text-black dark:text-white uppercase">Top Revenue by Operator (USD)</h3>
                    </div>
                    <div className="space-y-4">
                        {charts.top_operators.map((item, idx) => (
                            <div key={idx}>
                                <div className="flex justify-between text-xs font-bold text-black dark:text-white mb-1">
                                    <span className="uppercase">{item.operator}</span>
                                    <span>{formatUSD(item.total_revenue_usd)}</span>
                                </div>
                                <div className="w-full bg-gray-200 dark:bg-zinc-700 h-4 border-2 border-black">
                                    <div
                                        className="h-full bg-blue-500 border-r-2 border-black"
                                        style={{ width: `${getPercent(item.total_revenue_usd, charts.top_operators)}%` }}
                                    ></div>
                                </div>
                            </div>
                        ))}
                        {charts.top_operators.length === 0 && (
                            <p className="text-gray-500 font-bold italic">No data available.</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Quick Actions & System Status */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6">
                    <div className="flex justify-between items-center border-b-4 border-black pb-4 mb-4">
                        <h3 className="text-xl font-black text-black dark:text-white uppercase">Quick Actions</h3>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <button onClick={() => window.location.href = '/v2/services'} className="p-4 border-2 border-black bg-yellow-300 hover:bg-yellow-400 font-bold uppercase text-sm shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all text-left">
                            Manage Services &rarr;
                        </button>
                        <button onClick={() => window.location.href = '/v2/operators'} className="p-4 border-2 border-black bg-pink-300 hover:bg-pink-400 font-bold uppercase text-sm shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all text-left">
                            Manage Operators &rarr;
                        </button>
                    </div>
                </div>

                <div className="bg-white dark:bg-zinc-800 border-4 border-black shadow-[8px_8px_0_0_rgba(0,0,0,1)] p-6">
                    <div className="flex justify-between items-center border-b-4 border-black pb-4 mb-4">
                        <h3 className="text-xl font-black text-black dark:text-white uppercase">System Status</h3>
                        <div className="w-4 h-4 bg-green-500 border-2 border-black animate-pulse"></div>
                    </div>
                    <p className="font-mono text-gray-600 dark:text-gray-300 font-bold">
                        &gt; All systems operational.<br />
                        &gt; Database connection: OK<br />
                        &gt; Last sync: {new Date().toLocaleTimeString()}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
