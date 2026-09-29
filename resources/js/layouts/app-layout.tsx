import { usePage } from '@inertiajs/react';
import { ClosedBanner } from '@/components/frituur';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import type { Ordering } from '@/types/frituur';

/**
 * Adminlayout: sidebar van het starterkit, flash-toasts en de rode balk als bestellen dicht is
 * (gedeelde prop `ordering`, 02-architect §4).
 */
export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    useFlashToast();
    const ordering = usePage().props.ordering as Ordering | undefined;

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {ordering && !ordering.is_open && (
                <ClosedBanner
                    message={ordering.closed_message}
                    className="mx-0 px-6"
                />
            )}
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {children}
            </div>
        </AppLayoutTemplate>
    );
}
