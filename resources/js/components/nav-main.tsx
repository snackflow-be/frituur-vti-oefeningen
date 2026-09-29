import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

/** Actief als het pad gelijk is of eronder ligt (/admin/bestellingen/12), nooit op een prefix van een woord. */
function isActivePath(current: string, href: NavItem['href']): boolean {
    const path = toUrl(href).replace(/[?#].*$/, '');

    return current === path || current.startsWith(`${path}/`);
}

export function NavMain({ items }: { items: NavItem[] }) {
    const { currentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="font-display tracking-wide uppercase">
                Beheer
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isActivePath(currentUrl, item.href)}
                            tooltip={{ children: item.title }}
                            className="h-10 text-base data-[active=true]:bg-primary data-[active=true]:text-primary-foreground"
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
