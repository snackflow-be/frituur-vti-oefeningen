import { useState } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    /** Foto-URL; `null` → gestileerde placeholder per categorie. */
    src: string | null;
    alt: string;
    /** Categorie-slug: `frieten`, `snacks`, `sauzen`, `dranken` of iets nieuws. */
    categorySlug?: string;
    /** Verhouding van het kader; standaard 4:3. */
    ratio?: 'square' | '4/3' | '16/9';
    /** Groot (productkaart) of klein (admin-miniatuur). */
    size?: 'sm' | 'lg';
    className?: string;
};

type Placeholder = { emoji: string; bg: string; label: string };

const placeholders: Record<string, Placeholder> = {
    frieten: { emoji: '🍟', bg: 'bg-[#2a2010]', label: 'Frieten' },
    snacks: { emoji: '🌭', bg: 'bg-[#2a1a12]', label: 'Snacks' },
    sauzen: { emoji: '🥫', bg: 'bg-[#1f2416]', label: 'Sauzen' },
    dranken: { emoji: '🥤', bg: 'bg-[#141f2a]', label: 'Dranken' },
};

const fallback: Placeholder = { emoji: '🍽️', bg: 'bg-secondary', label: '' };

export function placeholderFor(categorySlug?: string): Placeholder {
    return (categorySlug && placeholders[categorySlug]) || fallback;
}

const ratios = {
    square: 'aspect-square',
    '4/3': 'aspect-[4/3]',
    '16/9': 'aspect-video',
};

/**
 * Productfoto met vaste verhouding (geen layout-shift). Zonder foto, of als de foto niet
 * laadt: donkere kaart met groot pictogram per categorie, nooit een grijs vlak.
 */
export function ProductImage({
    src,
    alt,
    categorySlug,
    ratio = '4/3',
    size = 'lg',
    className,
}: Props) {
    const [failed, setFailed] = useState(false);
    const [loaded, setLoaded] = useState(false);
    const showPlaceholder = !src || failed;
    const placeholder = placeholderFor(categorySlug);

    return (
        <div
            className={cn(
                'relative w-full overflow-hidden rounded-lg',
                ratios[ratio],
                showPlaceholder ? placeholder.bg : 'bg-secondary',
                className,
            )}
        >
            {showPlaceholder ? (
                <div
                    className="absolute inset-0 flex items-center justify-center"
                    role="img"
                    aria-label={alt}
                >
                    <span
                        className={cn(
                            'leading-none select-none',
                            size === 'lg' ? 'text-7xl sm:text-8xl' : 'text-2xl',
                        )}
                        aria-hidden
                    >
                        {placeholder.emoji}
                    </span>
                </div>
            ) : (
                <img
                    src={src}
                    alt={alt}
                    loading="lazy"
                    decoding="async"
                    onLoad={() => setLoaded(true)}
                    onError={() => setFailed(true)}
                    className={cn(
                        'absolute inset-0 size-full object-cover transition-opacity duration-300',
                        loaded ? 'opacity-100' : 'opacity-0',
                    )}
                />
            )}
        </div>
    );
}
