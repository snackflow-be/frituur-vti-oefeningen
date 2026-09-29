import { cn } from '@/lib/utils';

function Bone({ className }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={cn(
                'rounded-lg bg-secondary motion-safe:animate-pulse',
                className,
            )}
        />
    );
}

/** Zelfde afmetingen als ProductCard, zodat niets verspringt als de data komt. */
export function ProductCardSkeleton() {
    return (
        <div className="overflow-hidden rounded-lg border border-border bg-card">
            <Bone className="aspect-[4/3] w-full rounded-none" />
            <div className="flex flex-col gap-3 p-4">
                <Bone className="h-6 w-2/3" />
                <Bone className="h-5 w-full" />
                <div className="flex items-center justify-between">
                    <Bone className="h-8 w-20" />
                    <Bone className="size-12" />
                </div>
            </div>
        </div>
    );
}

/** Raster voor productkaarten: 1 kolom op gsm, 2 op tablet, 3 op desktop. */
export const productGridClass =
    'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3';

/** Menu tijdens het laden: navigatie + twee categorieën met elk drie kaarten. */
export function MenuSkeleton() {
    return (
        <div aria-busy="true" aria-label="Menu wordt geladen">
            <div className="-mx-4 flex gap-2 border-b border-border px-4 py-3">
                {[0, 1, 2, 3].map((i) => (
                    <Bone key={i} className="h-11 w-24 shrink-0" />
                ))}
            </div>
            {[0, 1].map((section) => (
                <section key={section} className="mt-8">
                    <Bone className="mb-4 h-9 w-40" />
                    <div className={productGridClass}>
                        {[0, 1, 2].map((i) => (
                            <ProductCardSkeleton key={i} />
                        ))}
                    </div>
                </section>
            ))}
        </div>
    );
}

/** Bevestiging/opvolgen tijdens het laden: nummer, stappen, lijnen. */
export function OrderSkeleton() {
    return (
        <div
            aria-busy="true"
            aria-label="Bestelling wordt geladen"
            className="flex flex-col gap-6 py-8"
        >
            <Bone className="h-5 w-40" />
            <Bone className="h-20 w-64" />
            <div className="grid grid-cols-4 gap-2">
                {[0, 1, 2, 3].map((i) => (
                    <Bone key={i} className="h-24" />
                ))}
            </div>
            <div className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
                {[0, 1, 2].map((i) => (
                    <Bone key={i} className="h-7 w-full" />
                ))}
            </div>
        </div>
    );
}

/** Keukenbord tijdens het laden: drie kolommen met een kaart. */
export function KitchenSkeleton() {
    return (
        <div
            aria-busy="true"
            aria-label="Bestellingen worden geladen"
            className="grid grid-cols-1 gap-6 md:grid-cols-3"
        >
            {[0, 1, 2].map((i) => (
                <div key={i} className="flex flex-col gap-4">
                    <Bone className="h-10 w-32" />
                    <Bone className="h-64 w-full" />
                </div>
            ))}
        </div>
    );
}
