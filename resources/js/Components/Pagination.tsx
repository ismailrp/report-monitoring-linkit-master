import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationProps {
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export default function Pagination({ links }: PaginationProps) {
    if (links.length <= 3) return null; // Don't show if only previous, next, and 1 page

    return (
        <div className="flex flex-wrap justify-center gap-2 mt-8">
            {links.map((link, key) => {
                // Parse label to handle HTML entities if necessary or just render directly
                let label = link.label;
                let isPrev = label.includes('&laquo;') || label.includes('Previous');
                let isNext = label.includes('&raquo;') || label.includes('Next');

                // Icon replacement for Previous/Next
                const content = isPrev ? <ChevronLeft className="w-4 h-4" /> :
                    isNext ? <ChevronRight className="w-4 h-4" /> :
                        <span dangerouslySetInnerHTML={{ __html: label }} />;

                return (
                    <div key={key}>
                        {link.url === null ? (
                            <div className="mr-1 mb-1 px-4 py-2 border-2 text-sm font-bold border-gray-300 text-gray-400 dark:border-gray-700 dark:text-gray-500 cursor-not-allowed">
                                {content}
                            </div>
                        ) : (
                            <Link
                                className={`mr-1 mb-1 px-4 py-2 border-2 text-sm font-bold transition-all shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:shadow-[4px_4px_0_0_rgba(0,0,0,1)] hover:-translate-y-[1px] hover:-translate-x-[1px] active:shadow-none active:translate-x-[2px] active:translate-y-[2px] ${link.active
                                        ? 'bg-black text-white border-black'
                                        : 'bg-white text-black border-black hover:bg-yellow-200'
                                    }`}
                                href={link.url}
                            >
                                {content}
                            </Link>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
