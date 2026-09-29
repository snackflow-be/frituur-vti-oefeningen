/**
 * Types van de publieke API (02-architect §3.1). Bedragen komen altijd als centen
 * (`*_cents`) én geformatteerd (`"€ 3,50"`).
 */

export type OrderStatus = 'nieuw' | 'bezig' | 'klaar' | 'afgehaald';

export type OpeningSlot = {
    from: string; // "11:30"
    to: string; // "14:00"
};

export type OpeningDay = {
    day: number; // 1 = maandag … 7 = zondag
    label: string; // "maandag"
    slots: OpeningSlot[]; // leeg = gesloten
};

export type Business = {
    name: string;
    address: string;
    phone: string;
    opening_hours: OpeningDay[];
};

export type Ordering = {
    is_open: boolean;
    closed_message: string | null;
};

export type Product = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price_cents: number;
    price: string;
    image_url: string | null;
    is_sold_out: boolean;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    products: Product[];
};

export type Menu = {
    business: Business;
    ordering: Ordering;
    categories: Category[];
};

export type OrderLine = {
    product_name: string;
    quantity: number;
    unit_price_cents: number;
    unit_price: string;
    line_total_cents: number;
    line_total: string;
};

export type Order = {
    id: number;
    number: string; // "VTI-042"
    token: string;
    status: OrderStatus;
    status_label: string;
    customer_name: string;
    customer_phone: string;
    total_cents: number;
    total: string;
    placed_at: string; // ISO 8601 met offset
    started_at: string | null;
    ready_at: string | null;
    picked_up_at: string | null;
    lines: OrderLine[];
};

export type PlaceOrderPayload = {
    customer_name: string;
    customer_phone: string;
    lines: { product_id: number; quantity: number }[];
};

export type PlaceOrderResponse = {
    order: Order;
    track_url: string;
};

export type CartItem = {
    product_id: number;
    quantity: number;
};
