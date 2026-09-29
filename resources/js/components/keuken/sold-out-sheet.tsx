import { Ban } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { BigButton } from '@/components/frituur';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { ApiError, apiGet, apiPatch } from '@/lib/api';
import { cn } from '@/lib/utils';
import { products as productsRoute } from '@/routes/keuken';
import { soldout } from '@/routes/keuken/products';
import type { KitchenCategory, KitchenProduct } from '@/types/staff';

/**
 * Paneel "Uitverkocht": alle producten per categorie met een grote schakelaar per product.
 * Laadt de lijst bij het openen; elke tik gaat meteen naar de server.
 */
export function SoldOutSheet() {
    const [open, setOpen] = useState(false);
    const [categories, setCategories] = useState<KitchenCategory[] | null>(
        null,
    );
    const [pendingId, setPendingId] = useState<number | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();

        apiGet<{ categories: KitchenCategory[] }>(
            productsRoute.url(),
            controller.signal,
        )
            .then((data) => setCategories(data.categories))
            .catch((error: unknown) => {
                if (error instanceof ApiError) {
                    toast.error(error.message);
                }
            });

        return () => controller.abort();
    }, [open]);

    const toggle = async (product: KitchenProduct) => {
        setPendingId(product.id);

        try {
            const { product: updated } = await apiPatch<{
                product: KitchenProduct;
            }>(soldout.url(product.id), { is_sold_out: !product.is_sold_out });

            setCategories((current) =>
                current
                    ? current.map((category) => ({
                          ...category,
                          products: category.products.map((p) =>
                              p.id === updated.id ? updated : p,
                          ),
                      }))
                    : current,
            );

            toast.success(
                updated.is_sold_out
                    ? `${updated.name} staat op uitverkocht.`
                    : `${updated.name} is weer te bestellen.`,
            );
        } catch (error) {
            toast.error(
                error instanceof ApiError
                    ? error.message
                    : 'Er ging iets mis. Probeer opnieuw.',
            );
        } finally {
            setPendingId(null);
        }
    };

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <BigButton variant="secondary" size="md">
                    <Ban aria-hidden />
                    Uitverkocht
                </BigButton>
            </SheetTrigger>
            <SheetContent
                side="right"
                className="w-full overflow-y-auto border-border bg-background sm:max-w-xl"
            >
                <SheetHeader className="px-6 pt-6">
                    <SheetTitle className="font-display text-3xl font-extrabold uppercase">
                        Uitverkocht
                    </SheetTitle>
                    <SheetDescription className="text-base text-muted-foreground">
                        Tik op een product om het uitverkocht te zetten of weer
                        te openen. Klanten zien dat meteen.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-8 px-6 pb-10">
                    {categories === null ? (
                        <p className="text-lg text-muted-foreground">
                            Producten worden geladen…
                        </p>
                    ) : (
                        categories.map((category) => (
                            <section key={category.id}>
                                <h3 className="mb-3 font-display text-xl font-extrabold tracking-wide text-muted-foreground uppercase">
                                    {category.name}
                                </h3>
                                <ul className="flex flex-col gap-2">
                                    {category.products.map((product) => (
                                        <li key={product.id}>
                                            <button
                                                type="button"
                                                aria-pressed={
                                                    product.is_sold_out
                                                }
                                                disabled={
                                                    pendingId === product.id
                                                }
                                                onClick={() =>
                                                    void toggle(product)
                                                }
                                                className={cn(
                                                    'flex min-h-14 w-full cursor-pointer items-center justify-between gap-4 rounded-lg border-2 px-4 py-2 text-left text-lg font-bold transition-colors disabled:opacity-50',
                                                    product.is_sold_out
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border bg-card hover:border-foreground',
                                                )}
                                            >
                                                <span>
                                                    {product.name}
                                                    {!product.is_visible && (
                                                        <span className="ml-2 text-sm font-normal opacity-70">
                                                            (verborgen)
                                                        </span>
                                                    )}
                                                </span>
                                                <span className="font-display text-sm tracking-wide uppercase">
                                                    {product.is_sold_out
                                                        ? 'Uitverkocht'
                                                        : 'Te bestellen'}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
