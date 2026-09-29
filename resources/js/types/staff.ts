/**
 * Types voor keuken en admin (02-architect §3.2 en §4). De bestelling zelf is `Order` uit
 * `types/frituur` (zelfde vorm als de publieke API).
 */
import type { Order, OrderStatus, Ordering } from '@/types/frituur';

export type KitchenBoard = {
    server_time: string; // ISO 8601 met offset
    ordering: Ordering;
    orders: Order[];
};

export type KitchenProduct = {
    id: number;
    name: string;
    is_sold_out: boolean;
    is_visible: boolean;
};

export type KitchenCategory = {
    id: number;
    name: string;
    slug: string;
    products: KitchenProduct[];
};

export type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    products_count?: number;
};

export type AdminProduct = {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    description: string | null;
    price_cents: number;
    image_url: string | null;
    is_visible: boolean;
    is_sold_out: boolean;
    sort_order: number;
};

export type AdminCategoryWithProducts = AdminCategory & {
    products: AdminProduct[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLink[];
};

export type OrderFilters = {
    q: string;
    from: string;
    to: string;
    status: OrderStatus | '';
};

export type StatusOption = {
    value: OrderStatus;
    label: string;
};

export type StatsPeriod = {
    revenue_cents: number;
    revenue: string;
    orders: number;
};

export type TopProduct = {
    product_name: string;
    quantity: number;
    revenue_cents: number;
};

export type DayStat = {
    date: string;
    label: string;
    revenue_cents: number;
    orders: number;
};

export type Stats = {
    today: StatsPeriod;
    week: StatsPeriod;
    top_today: TopProduct[];
    top_week: TopProduct[];
    per_day: DayStat[];
};

export type OpeningSlotForm = {
    from: string;
    to: string;
};

export type OpeningDayForm = {
    day: number;
    label: string;
    slots: OpeningSlotForm[];
};

export type SettingsForm = {
    business_name: string;
    address: string;
    phone: string;
    opening_hours: OpeningDayForm[];
};

export type StaffMember = {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
};
