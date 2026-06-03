import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from 'ziggy-js';

declare global {
    interface Window {
        axios: AxiosInstance;
        route: typeof ziggyRoute;
        Ziggy: any;
    }

    var route: typeof ziggyRoute;
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps { }
}

export interface AppPageProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            roles?: { name: string }[];
            permissions?: { name: string }[];
        };
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, unknown>;
    };
}
