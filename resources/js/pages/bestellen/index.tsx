import { Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Send, Trash2 } from 'lucide-react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import {
    BigButton,
    ClosedBanner,
    EmptyState,
    Field,
    OrderSkeleton,
    Price,
    ProductImage,
    QuantityStepper,
} from '@/components/frituur';
import { CART_MAX_QUANTITY, useCart } from '@/hooks/use-cart';
import { indexMenu, useMenu } from '@/hooks/use-menu';
import FrituurLayout from '@/layouts/frituur-layout';
import { ApiError, apiPost } from '@/lib/api';
import { bevestiging, home } from '@/routes';
import type { PlaceOrderPayload, PlaceOrderResponse } from '@/types/frituur';

const CUSTOMER_STORAGE_KEY = 'frituur.customer.v1';

type Customer = { name: string; phone: string };

function readCustomer(): Customer {
    try {
        const raw = window.localStorage.getItem(CUSTOMER_STORAGE_KEY);
        const parsed: unknown = raw ? JSON.parse(raw) : null;

        if (parsed && typeof parsed === 'object') {
            const { name, phone } = parsed as Partial<Customer>;

            return {
                name: typeof name === 'string' ? name : '',
                phone: typeof phone === 'string' ? phone : '',
            };
        }
    } catch {
        // Geen opslag: leeg formulier.
    }

    return { name: '', phone: '' };
}

function rememberCustomer(customer: Customer) {
    try {
        window.localStorage.setItem(
            CUSTOMER_STORAGE_KEY,
            JSON.stringify(customer),
        );
    } catch {
        // Niet erg: de klant typt het de volgende keer opnieuw.
    }
}

type FieldErrors = { customer_name?: string; customer_phone?: string };

export default function BestellenPage() {
    const { menu, loading, error: menuError, reload } = useMenu();
    const cart = useCart();
    const products = useMemo(() => indexMenu(menu), [menu]);

    const [customer, setCustomer] = useState<Customer>({ name: '', phone: '' });

    // Na de eerste render (hydratatie-veilig): laatst gebruikte naam en nummer invullen.
    useEffect(() => {
        setCustomer(readCustomer());
    }, []);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [formError, setFormError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (menu && cart.hydrated) {
            cart.keepOnly(new Set(products.keys()));
        }
    }, [menu, products, cart.hydrated, cart.keepOnly]);

    const lines = cart.items
        .map((item) => {
            const entry = products.get(item.product_id);

            return entry ? { ...entry, quantity: item.quantity } : null;
        })
        .filter((line) => line !== null);

    const soldOutLines = lines.filter((line) => line.product.is_sold_out);
    const totalCents = lines.reduce(
        (sum, line) =>
            line.product.is_sold_out
                ? sum
                : sum + line.product.price_cents * line.quantity,
        0,
    );

    const isOpen = menu?.ordering.is_open ?? true;
    const businessName = menu?.business.name ?? 'Frituur VTI';
    const canSubmit =
        isOpen && lines.length > 0 && soldOutLines.length === 0 && !submitting;

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        setSubmitting(true);
        setFieldErrors({});
        setFormError(null);

        const payload: PlaceOrderPayload = {
            customer_name: customer.name.trim(),
            customer_phone: customer.phone.trim(),
            lines: lines.map((line) => ({
                product_id: line.product.id,
                quantity: line.quantity,
            })),
        };

        try {
            const response = await apiPost<PlaceOrderResponse>(
                '/api/orders',
                payload,
            );

            rememberCustomer({
                name: payload.customer_name,
                phone: payload.customer_phone,
            });
            cart.clear();
            router.visit(bevestiging.url({ token: response.order.token }));
        } catch (error) {
            setSubmitting(false);

            if (!(error instanceof ApiError)) {
                setFormError('Er ging iets mis. Probeer opnieuw.');

                return;
            }

            if (error.status === 422) {
                const nextFieldErrors: FieldErrors = {
                    customer_name:
                        error.fieldError('customer_name') ?? undefined,
                    customer_phone:
                        error.fieldError('customer_phone') ?? undefined,
                };
                setFieldErrors(nextFieldErrors);

                const messages: string[] = [];
                const orderingError = error.fieldError('ordering');

                if (orderingError) {
                    messages.push(orderingError);
                    void reload();
                }

                // Lijnfouten: het product is weg of uitverkocht → uit het mandje, melding tonen.
                let removed = false;

                Object.entries(error.errors).forEach(([key, value]) => {
                    const match = /^lines\.(\d+)\./.exec(key);

                    if (!match) {
                        return;
                    }

                    const line = payload.lines[Number(match[1])];

                    if (line) {
                        cart.remove(line.product_id);
                        removed = true;
                    }

                    if (value[0]) {
                        messages.push(value[0]);
                    }
                });

                if (removed) {
                    messages.push(
                        'We haalden dat uit je mandje. Kijk het na en stuur opnieuw door.',
                    );
                    void reload();
                }

                if (error.fieldError('lines') && !removed) {
                    messages.push(error.fieldError('lines') ?? '');
                }

                if (
                    messages.length === 0 &&
                    !nextFieldErrors.customer_name &&
                    !nextFieldErrors.customer_phone
                ) {
                    messages.push(error.message);
                }

                setFormError(messages.length > 0 ? messages.join(' ') : null);

                return;
            }

            if (error.status === 429) {
                const seconds = error.retryAfter;
                setFormError(
                    seconds
                        ? `Te veel bestellingen na elkaar. Probeer over ${seconds} seconden opnieuw.`
                        : error.message,
                );

                return;
            }

            setFormError(error.message);
        }
    }

    return (
        <FrituurLayout
            title="Mandje"
            businessName={businessName}
            cartCount={cart.count}
            reserveCartBar={false}
        >
            <div className="py-6 sm:py-10">
                <Link
                    href={home.url()}
                    className="inline-flex min-h-11 items-center gap-2 text-base font-bold text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-5" aria-hidden />
                    Verder kiezen
                </Link>

                <h1 className="mt-2 uppercase">Je mandje</h1>

                {menu && !isOpen && (
                    <ClosedBanner
                        message={menu.ordering.closed_message}
                        className="mt-6"
                    />
                )}

                {loading && !menu && <OrderSkeleton />}

                {!loading && !menu && (
                    <EmptyState
                        icon="📡"
                        title="Menu niet geladen"
                        text={
                            menuError ??
                            'We konden je mandje niet nakijken. Probeer opnieuw.'
                        }
                        action={
                            <BigButton onClick={() => void reload()}>
                                Opnieuw proberen
                            </BigButton>
                        }
                        className="mt-8"
                    />
                )}

                {menu && cart.hydrated && lines.length === 0 && (
                    <EmptyState
                        icon="🛒"
                        title="Je mandje is leeg"
                        text="Kies iets lekkers uit het menu en het staat hier meteen klaar."
                        action={
                            <BigButton asChild size="xl">
                                <Link href={home.url()}>Naar het menu</Link>
                            </BigButton>
                        }
                        className="mt-8"
                    />
                )}

                {menu && lines.length > 0 && (
                    <form
                        onSubmit={(event) => void submit(event)}
                        noValidate
                        className="mt-6 flex flex-col gap-8"
                    >
                        <ul className="flex flex-col gap-3">
                            {lines.map((line, index) => (
                                <li
                                    key={line.product.id}
                                    className="flex items-center gap-4 rounded-lg border border-border bg-card p-3 motion-safe:animate-fade-up"
                                    style={{
                                        animationDelay: `${Math.min(index, 8) * 40}ms`,
                                    }}
                                >
                                    <ProductImage
                                        src={line.product.image_url}
                                        alt={line.product.name}
                                        categorySlug={line.categorySlug}
                                        ratio="square"
                                        size="sm"
                                        className={
                                            line.product.is_sold_out
                                                ? 'w-16 shrink-0 opacity-40 grayscale'
                                                : 'w-16 shrink-0'
                                        }
                                    />
                                    <div className="min-w-0 flex-1">
                                        <h3 className="text-[1.15rem] leading-tight">
                                            {line.product.name}
                                        </h3>
                                        {line.product.is_sold_out ? (
                                            <p className="mt-1 text-base font-bold text-primary">
                                                Uitverkocht: haal dit uit je
                                                mandje
                                            </p>
                                        ) : (
                                            <p className="mt-1 text-base text-muted-foreground">
                                                {line.product.price} per stuk
                                            </p>
                                        )}
                                        {!line.product.is_sold_out && (
                                            <Price
                                                cents={
                                                    line.product.price_cents *
                                                    line.quantity
                                                }
                                                size="md"
                                                className="mt-1 block"
                                            />
                                        )}
                                    </div>
                                    {line.product.is_sold_out ? (
                                        <BigButton
                                            type="button"
                                            variant="danger"
                                            size="icon"
                                            aria-label={`${line.product.name} verwijderen`}
                                            onClick={() =>
                                                cart.remove(line.product.id)
                                            }
                                        >
                                            <Trash2 aria-hidden />
                                        </BigButton>
                                    ) : (
                                        <QuantityStepper
                                            value={line.quantity}
                                            max={CART_MAX_QUANTITY}
                                            label={line.product.name}
                                            disabled={!isOpen || submitting}
                                            onChange={(quantity) =>
                                                cart.setQuantity(
                                                    line.product.id,
                                                    quantity,
                                                )
                                            }
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>

                        <div className="flex items-center justify-between rounded-lg border-2 border-border px-4 py-4">
                            <div>
                                <p className="text-lg font-bold">Totaal</p>
                                <p className="text-sm text-muted-foreground">
                                    Je betaalt bij afhaling (cash of kaart).
                                </p>
                            </div>
                            <Price
                                cents={totalCents}
                                size="xl"
                                className="shrink-0 whitespace-nowrap"
                            />
                        </div>

                        {isOpen && (
                            <fieldset
                                className="flex flex-col gap-5"
                                disabled={submitting}
                            >
                                <legend className="mb-4 font-display text-2xl font-extrabold uppercase">
                                    Wie haalt af?
                                </legend>
                                <Field
                                    label="Je naam"
                                    name="customer_name"
                                    autoComplete="name"
                                    maxLength={40}
                                    required
                                    value={customer.name}
                                    onChange={(event) =>
                                        setCustomer((current) => ({
                                            ...current,
                                            name: event.target.value,
                                        }))
                                    }
                                    error={fieldErrors.customer_name}
                                    placeholder="Bv. Jef"
                                />
                                <Field
                                    label="Je gsm-nummer"
                                    name="customer_phone"
                                    type="tel"
                                    inputMode="tel"
                                    autoComplete="tel"
                                    maxLength={20}
                                    required
                                    value={customer.phone}
                                    onChange={(event) =>
                                        setCustomer((current) => ({
                                            ...current,
                                            phone: event.target.value,
                                        }))
                                    }
                                    error={fieldErrors.customer_phone}
                                    hint="Enkel als er iets is met je bestelling."
                                    placeholder="0470 12 34 56"
                                />
                            </fieldset>
                        )}

                        {formError && (
                            <div
                                role="alert"
                                className="flex items-start gap-3 rounded-lg border-2 border-primary px-4 py-3 text-base font-bold text-primary motion-safe:animate-fade-in"
                            >
                                <AlertTriangle
                                    className="mt-0.5 size-5 shrink-0"
                                    aria-hidden
                                />
                                <span>{formError}</span>
                            </div>
                        )}

                        {isOpen ? (
                            <BigButton
                                type="submit"
                                size="xl"
                                block
                                loading={submitting}
                                disabled={!canSubmit}
                            >
                                <Send aria-hidden />
                                Bestelling doorsturen
                            </BigButton>
                        ) : (
                            <p className="text-center text-lg text-muted-foreground">
                                Je mandje blijft bewaard. Probeer straks
                                opnieuw.
                            </p>
                        )}
                    </form>
                )}
            </div>
        </FrituurLayout>
    );
}
