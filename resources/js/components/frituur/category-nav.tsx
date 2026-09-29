import { cn } from '@/lib/utils';

export type CategoryNavItem = { slug: string; name: string };

type Props = {
    categories: CategoryNavItem[];
    activeSlug?: string | null;
    /** Zonder `onSelect` springt de link naar `#cat-<slug>`. */
    onSelect?: (slug: string) => void;
    /** Hoogte van de header erboven, om de sticky positie juist te zetten. */
    offsetClassName?: string;
    className?: string;
};

/**
 * Sticky categorienavigatie: horizontaal scrollende pillen, actieve categorie in accent.
 * De pagina beslist welke categorie actief is (IntersectionObserver of klik).
 */
export function CategoryNav({
    categories,
    activeSlug,
    onSelect,
    offsetClassName = 'top-16',
    className,
}: Props) {
    return (
        <nav
            aria-label="Categorieën"
            className={cn(
                'sticky z-30 -mx-4 border-b border-border bg-background/95 backdrop-blur-sm',
                offsetClassName,
                className,
            )}
        >
            <ul className="flex scrollbar-none gap-2 overflow-x-auto px-4 py-3">
                {categories.map((category) => {
                    const active = category.slug === activeSlug;

                    return (
                        <li key={category.slug} className="shrink-0">
                            <a
                                href={`#cat-${category.slug}`}
                                onClick={(event) => {
                                    if (onSelect) {
                                        event.preventDefault();
                                        onSelect(category.slug);
                                    }
                                }}
                                aria-current={active ? 'true' : undefined}
                                className={cn(
                                    'flex h-11 items-center rounded-lg border-2 px-4 font-display text-base font-extrabold tracking-wide uppercase transition-colors',
                                    active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-card text-foreground hover:border-foreground',
                                )}
                            >
                                {category.name}
                            </a>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
