import { Plus } from 'lucide-react';
import { Price } from '@/components/frituur/price';
import { ProductImage } from '@/components/frituur/product-image';
import { QuantityStepper } from '@/components/frituur/quantity-stepper';
import { cn } from '@/lib/utils';
import type { Product } from '@/types/frituur';

type Props = {
    product: Product;
    categorySlug: string;
    /** Aantal al in het mandje; > 0 toont de stepper in plaats van de +knop. */
    quantity?: number;
    onAdd?: (product: Product) => void;
    onChangeQuantity?: (product: Product, quantity: number) => void;
    /** Bestellen gesloten: +knop uit. */
    disabled?: boolean;
    /** Volgnummer voor de subtiele stagger bij het inladen. */
    index?: number;
    className?: string;
};

/**
 * Productkaart: grote foto (of placeholder), naam ≥ 1.15rem, korte omschrijving, prijs
 * vet en groot, grote +knop (48 px). Uitverkocht: label, geen knop, foto gedempt.
 */
export function ProductCard({
    product,
    categorySlug,
    quantity = 0,
    onAdd,
    onChangeQuantity,
    disabled = false,
    index = 0,
    className,
}: Props) {
    const orderable = !product.is_sold_out && !disabled;

    return (
        <article
            className={cn(
                'flex flex-col overflow-hidden rounded-lg border border-border bg-card motion-safe:animate-fade-up',
                quantity > 0 && 'border-primary',
                className,
            )}
            style={{ animationDelay: `${Math.min(index, 8) * 40}ms` }}
            aria-label={product.name}
        >
            <div className="relative">
                <ProductImage
                    src={product.image_url}
                    alt={product.name}
                    categorySlug={categorySlug}
                    className={cn(
                        'rounded-none',
                        product.is_sold_out && 'opacity-40 grayscale',
                    )}
                />
                {product.is_sold_out && (
                    <span className="absolute top-3 left-3 rounded-lg bg-foreground px-3 py-1.5 font-display text-sm font-extrabold tracking-wide text-background uppercase">
                        Uitverkocht
                    </span>
                )}
                {quantity > 0 && (
                    <span className="absolute top-3 right-3 flex h-9 min-w-9 items-center justify-center rounded-lg bg-primary px-2 font-display text-lg font-extrabold text-primary-foreground tabular-nums">
                        {quantity}
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="flex-1">
                    <h3 className="text-[1.25rem] leading-tight">
                        {product.name}
                    </h3>
                    {product.description && (
                        <p className="mt-1 text-base leading-snug text-muted-foreground">
                            {product.description}
                        </p>
                    )}
                </div>

                <div className="flex items-center justify-between gap-3">
                    <Price cents={product.price_cents} size="lg" />

                    {product.is_sold_out ? (
                        <span className="text-base font-bold text-muted-foreground">
                            Op
                        </span>
                    ) : quantity > 0 && onChangeQuantity ? (
                        <QuantityStepper
                            value={quantity}
                            onChange={(next) => onChangeQuantity(product, next)}
                            label={product.name}
                            disabled={disabled}
                        />
                    ) : (
                        <button
                            type="button"
                            onClick={() => onAdd?.(product)}
                            disabled={!orderable}
                            aria-label={`${product.name} toevoegen`}
                            className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground transition-colors hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <Plus
                                className="size-7"
                                strokeWidth={3}
                                aria-hidden
                            />
                        </button>
                    )}
                </div>
            </div>
        </article>
    );
}
