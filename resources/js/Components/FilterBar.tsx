import React, { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Search, Download, Calendar, X } from 'lucide-react';
import { PageProps } from '@/types/global';

interface FilterBarProps {
    countries?: { id: number; country: string }[];
    operators?: { id: number; operator: string }[];
    services?: { id: number; service: string }[];
    placeholder?: string;
    enableDateRange?: boolean;
    enableExport?: boolean;
    onExport?: () => void;
    periods?: { start_date: string, end_date: string, label: string }[];
}

export default function FilterBar({
    countries = [],
    operators = [],
    services = [],
    placeholder = 'Search...',
    enableDateRange = false,
    enableExport = false,
    onExport,
    periods = []
}: FilterBarProps) {
    const { url } = usePage<PageProps>();
    const queryParams = new URLSearchParams(window.location.search);

    // State for filters
    const [search, setSearch] = useState(queryParams.get('search') || '');
    const [startDate, setStartDate] = useState(queryParams.get('start_date') || '');
    const [endDate, setEndDate] = useState(queryParams.get('end_date') || '');

    // Dropdown states
    const [selectedCountry, setSelectedCountry] = useState(queryParams.get('country') || '');
    const [selectedOperator, setSelectedOperator] = useState(queryParams.get('operator') || '');
    const [selectedService, setSelectedService] = useState(queryParams.get('service') || '');

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (queryParams.get('search') || '')) {
                applyFilters({ search });
            }
        }, 500);

        return () => clearTimeout(timer);
    }, [search]);

    // Apply filters to URL
    const applyFilters = (newFilters: Record<string, string>) => {
        const currentParams = new URLSearchParams(window.location.search);

        Object.keys(newFilters).forEach(key => {
            if (newFilters[key]) {
                currentParams.set(key, newFilters[key]);
            } else {
                currentParams.delete(key);
            }
        });

        // Reset page to 1 when filtering
        currentParams.delete('page');

        router.get(`${window.location.pathname}?${currentParams.toString()}`, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    };

    const handleDateChange = () => {
        applyFilters({
            start_date: startDate,
            end_date: endDate
        });
    };

    // Trigger date filter when both dates are present or cleared
    useEffect(() => {
        if ((startDate && endDate) || (!startDate && !endDate && (queryParams.get('start_date') || queryParams.get('end_date')))) {
            handleDateChange();
        }
    }, [startDate, endDate]);

    // Handlers for dropdowns
    const handleCountryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value;
        setSelectedCountry(value);
        applyFilters({ country: value });
    };

    const handleOperatorChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value;
        setSelectedOperator(value);
        applyFilters({ operator: value });
    };

    const handleServiceChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value;
        setSelectedService(value);
        applyFilters({ service: value });
    };

    // const clearFilters = () => { ... } // (Optional: implement if needed)

    return (
        <div className="flex flex-col gap-4 mb-6 bg-white dark:bg-zinc-800 p-4 border-l-4 border-b-4 border-black dark:border-white shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:shadow-[4px_4px_0_0_rgba(255,255,255,1)]">
            <div className="flex flex-col sm:flex-row gap-4 justify-between items-center">
                {/* Search */}
                <div className="relative w-full sm:w-64 lg:w-96">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search className="h-5 w-5 text-black dark:text-white" />
                    </div>
                    <input
                        type="text"
                        className="block w-full pl-10 pr-3 py-2 border-2 border-black dark:border-white bg-white dark:bg-zinc-800 text-black dark:text-white placeholder-gray-500 focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] transition-all sm:text-sm font-bold"
                        placeholder={placeholder}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    {search && (
                        <button
                            onClick={() => setSearch('')}
                            className="absolute inset-y-0 right-0 pr-3 flex items-center text-black hover:text-gray-600"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    )}
                </div>

                <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    {/* Date Range or Period Dropdown */}
                    {enableDateRange && periods && periods.length > 0 ? (
                        <select
                            value={startDate && endDate ? `${startDate}|${endDate}` : ''}
                            onChange={(e) => {
                                const val = e.target.value;
                                if (val) {
                                    const [s, eDate] = val.split('|');
                                    setStartDate(s);
                                    setEndDate(eDate);
                                    applyFilters({ start_date: s, end_date: eDate });
                                } else {
                                    setStartDate('');
                                    setEndDate('');
                                    applyFilters({ start_date: '', end_date: '' });
                                }
                            }}
                            className="block w-full sm:w-64 pl-3 pr-10 py-2 text-base border-2 border-black dark:border-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] sm:text-sm bg-white dark:bg-zinc-800 text-black dark:text-white font-bold transition-all"
                        >
                            <option value="">All Periods</option>
                            {periods.map((p, i) => (
                                <option key={i} value={`${p.start_date}|${p.end_date}`}>{p.label}</option>
                            ))}
                        </select>
                    ) : enableDateRange && (
                        <div className="flex items-center gap-2">
                            <div className="relative">
                                <input
                                    type="date"
                                    className="pl-3 pr-3 py-2 border-2 border-black dark:border-white text-sm bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] transition-all font-bold"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>
                            <span className="text-black dark:text-white font-bold text-lg">-</span>
                            <div className="relative">
                                <input
                                    type="date"
                                    className="pl-3 pr-3 py-2 border-2 border-black dark:border-white text-sm bg-white dark:bg-zinc-800 text-black dark:text-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] transition-all font-bold"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>
                        </div>
                    )}

                    {/* Export Button */}
                    {enableExport && (
                        <button
                            onClick={onExport}
                            className="inline-flex items-center px-4 py-2 border-2 border-black dark:border-white text-sm font-bold text-white bg-green-600 hover:bg-green-700 hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:hover:shadow-[4px_4px_0_0_rgba(255,255,255,1)] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] transition-all"
                        >
                            <Download className="h-4 w-4 mr-2" />
                            Export
                        </button>
                    )}
                </div>
            </div>

            {/* Filters Row (Dropdowns) */}
            {((countries || []).length > 0 || (operators || []).length > 0 || (services || []).length > 0) && (
                <div className="flex flex-wrap gap-4 pt-4 border-t-2 border-black dark:border-white">

                    {(countries || []).length > 0 && (
                        <select
                            value={selectedCountry}
                            onChange={handleCountryChange}
                            className="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-2 border-black dark:border-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] sm:text-sm bg-white dark:bg-zinc-800 text-black dark:text-white font-bold transition-all"
                        >
                            <option value="">All Countries</option>
                            {countries.map((c) => (
                                <option key={c.id} value={c.id}>{c.country}</option>
                            ))}
                        </select>
                    )}

                    {(operators || []).length > 0 && (
                        <select
                            value={selectedOperator}
                            onChange={handleOperatorChange}
                            className="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-2 border-black dark:border-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] sm:text-sm bg-white dark:bg-zinc-800 text-black dark:text-white font-bold transition-all"
                        >
                            <option value="">All Operators</option>
                            {operators.map((o) => (
                                <option key={o.id} value={o.id}>{o.operator}</option>
                            ))}
                        </select>
                    )}

                    {(services || []).length > 0 && (
                        <select
                            value={selectedService}
                            onChange={handleServiceChange}
                            className="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-2 border-black dark:border-white focus:outline-none focus:shadow-[4px_4px_0_0_rgba(0,0,0,1)] dark:focus:shadow-[4px_4px_0_0_rgba(255,255,255,1)] sm:text-sm bg-white dark:bg-zinc-800 text-black dark:text-white font-bold transition-all"
                        >
                            <option value="">All Services</option>
                            {services.map((s) => (
                                <option key={s.id} value={s.id}>{s.service}</option>
                            ))}
                        </select>
                    )}
                </div>
            )}
        </div>
    );
}
