import { RefreshCw } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import {
    BigButton,
    CartBar,
    CategoryNav,
    ClosedBanner,
    EmptyState,
    FooterInfo,
    Hero,
    MenuSkeleton,
    ProductCard,
    productGridClass,
} from '@/components/frituur';
import { useCart } from '@/hooks/use-cart';
import { indexMenu, useMenu } from '@/hooks/use-menu';
import FrituurLayout from '@/layouts/frituur-layout';
import { bestellen } from '@/routes';
import type { Menu } from '@/types/frituur';

/** Hoogte van header (4rem) + categorienav (~4.25rem): daaronder telt een sectie als "actief". */
const ACTIVE_OFFSET_PX = 140;

function useActiveCategory(menu: Menu | null) {
    const [activeSlug, setActiveSlug] = useState<string | null>(null);

    useEffect(() => {
        if (!menu || menu.categories.length === 0) {
            return;
        }

        const slugs = menu.categories.map((category) => category.slug);
        let frame = 0;

        const measure = () => {
            frame = 0;
            let current = slugs[0];

            // Helemaal onderaan: de laatste categorie kan nooit tot bovenaan scrollen.
            const atBottom =
                window.innerHeight + window.scrollY >=
                document.documentElement.scrollHeight - 2;

            if (atBottom) {
                setActiveSlug(slugs[slugs.length - 1]);

                return;
            }

            for (const slug of slugs) {
                const element = document.getElementById(`cat-${slug}`);

                if (
                    element &&
                    element.getBoundingClientRect().top <= ACTIVE_OFFSET_PX
                ) {
                    current = slug;
                }
            }

            setActiveSlug(current);
        };

        const onScroll = () => {
            if (frame === 0) {
                frame = window.requestAnimationFrame(measure);
            }
        };

        measure();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);

            if (frame !== 0) {
                window.cancelAnimationFrame(frame);
            }
        };
    }, [menu]);

    const scrollTo = (slug: string) => {
        setActiveSlug(slug);
        document
            .getElementById(`cat-${slug}`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return { activeSlug, scrollTo };
}

type Props = { heroUrl?: string | null };

export default function MenuPage({ heroUrl = null }: Props) {
    const { menu, loading, error, reload } = useMenu();
    const cart = useCart();
    const { activeSlug, scrollTo } = useActiveCategory(menu);

    const products = useMemo(() => indexMenu(menu), [menu]);

    // Producten die uit het menu verdwenen (verborgen/verwijderd) horen niet meer in het mandje.
    useEffect(() => {
        if (menu && cart.hydrated) {
            cart.keepOnly(new Set(products.keys()));
        }
    }, [menu, products, cart.hydrated, cart.keepOnly]);

    const totalCents = cart.items.reduce((sum, item) => {
        const entry = products.get(item.product_id);

        return entry ? sum + entry.product.price_cents * item.quantity : sum;
    }, 0);

    const isOpen = menu?.ordering.is_open ?? true;
    const businessName = menu?.business.name ?? 'Frituur VTI';

    return (
        <FrituurLayout
            title="Menu"
            businessName={businessName}
            cartCount={cart.count}
            cartHref={bestellen.url()}
            footer={menu ? <FooterInfo business={menu.business} /> : null}
        >
            <Hero
                businessName={businessName}
                imageUrl={heroUrl}
                onCta={() =>
                    document
                        .getElementById('menu')
                        ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
                }
            />

            {menu && !isOpen && (
                <ClosedBanner message={menu.ordering.closed_message} />
            )}

            <div id="menu" className="scroll-mt-16">
                {loading && !menu && <MenuSkeleton />}

                {!loading && !menu && (
                    <EmptyState
                        icon="📡"
                        title="Menu niet geladen"
                        text={error ?? 'Het menu kon niet geladen worden.'}
                        action={
                            <BigButton onClick={() => void reload()}>
                                <RefreshCw aria-hidden />
                                Opnieuw proberen
                            </BigButton>
                        }
                        className="my-8"
                    />
                )}

                {menu && menu.categories.length === 0 && (
                    <EmptyState
                        title="Nog geen menu"
                        text="De frituur heeft nog geen producten online gezet. Kom straks terug."
                        className="my-8"
                    />
                )}

                {menu && menu.categories.length > 0 && (
                    <>
                        <CategoryNav
                            categories={menu.categories}
                            activeSlug={activeSlug}
                            onSelect={scrollTo}
                            offsetClassName="top-16"
                        />

                        {menu.categories.map((category) => (
                            <section
                                key={category.id}
                                id={`cat-${category.slug}`}
                                aria-labelledby={`cat-${category.slug}-title`}
                                className="mt-8 scroll-mt-32"
                            >
                                <h2
                                    id={`cat-${category.slug}-title`}
                                    className="mb-4 uppercase"
                                >
                                    {category.name}
                                </h2>
                                <div className={productGridClass}>
                                    {category.products.map((product, index) => (
                                        <ProductCard
                                            key={product.id}
                                            product={product}
                                            categorySlug={category.slug}
                                            quantity={cart.quantityOf(
                                                product.id,
                                            )}
                                            onAdd={() => cart.add(product.id)}
                                            onChangeQuantity={(_, quantity) =>
                                                cart.setQuantity(
                                                    product.id,
                                                    quantity,
                                                )
                                            }
                                            disabled={!isOpen}
                                            index={index}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}

                        {error && (
                            <p
                                role="status"
                                className="mt-8 text-center text-base text-muted-foreground"
                            >
                                Het menu kon niet vernieuwd worden ({error})
                            </p>
                        )}
                    </>
                )}
            </div>

            <CartBar
                count={cart.count}
                totalCents={totalCents}
                href={bestellen.url()}
                disabled={!isOpen}
            />
        </FrituurLayout>
    );
}
