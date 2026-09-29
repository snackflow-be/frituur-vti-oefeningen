import { Link } from '@inertiajs/react';
import {
    BarChart3,
    ChefHat,
    DoorOpen,
    Globe,
    LayoutList,
    Package,
    Receipt,
    Settings2,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home, keuken } from '@/routes';
import { stats } from '@/routes/admin';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { edit as orderingEdit } from '@/routes/admin/ordering';
import { index as ordersIndex } from '@/routes/admin/orders';
import { index as productsIndex } from '@/routes/admin/products';
import { edit as settingsEdit } from '@/routes/admin/settings';
import { index as staffIndex } from '@/routes/admin/staff';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Bestellingen', href: ordersIndex(), icon: Receipt },
    { title: 'Cijfers', href: stats(), icon: BarChart3 },
    { title: 'Producten', href: productsIndex(), icon: Package },
    { title: 'Categorieën', href: categoriesIndex(), icon: LayoutList },
    { title: 'Bestellen open/dicht', href: orderingEdit(), icon: DoorOpen },
    { title: 'Instellingen', href: settingsEdit(), icon: Settings2 },
    { title: 'Personeel', href: staffIndex(), icon: Users },
];

const footerNavItems: NavItem[] = [
    { title: 'Keukenscherm', href: keuken(), icon: ChefHat },
    { title: 'Site van de klant', href: home(), icon: Globe },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={ordersIndex()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
