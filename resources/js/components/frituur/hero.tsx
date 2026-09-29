import { ArrowDown } from 'lucide-react';
import { useState } from 'react';
import { BigButton } from '@/components/frituur/big-button';
import { cn } from '@/lib/utils';

type Props = {
    businessName: string;
    tagline?: string;
    /** Sfeerfoto (bv. `/img/hero.webp`). Laadt ze niet, dan blijft de tekstvariant staan. */
    imageUrl?: string | null;
    ctaLabel?: string;
    /** Klik op "Bestel nu"; standaard scrollt de pagina naar `#menu`. */
    onCta?: () => void;
    ctaHref?: string;
    className?: string;
};

/**
 * Hero bovenaan het menu: vaste hoogte (geen layout-shift), naam reusachtig in Archivo,
 * één knop "Bestel nu". Met foto: foto vult het blok, donkere plaat onder de tekst.
 * Zonder foto: warm-donker blok met een groot pictogram als decor.
 */
export function Hero({
    businessName,
    tagline = 'Vers gebakken. Meteen klaar. Bestel op je gsm, haal af aan de toog.',
    imageUrl,
    ctaLabel = 'Bestel nu',
    onCta,
    ctaHref = '#menu',
    className,
}: Props) {
    const [loaded, setLoaded] = useState(false);
    const [failed, setFailed] = useState(false);
    const hasImage = Boolean(imageUrl) && !failed;

    return (
        <section
            className={cn(
                'relative -mx-4 flex min-h-[26rem] items-end overflow-hidden bg-[#1c1712] sm:min-h-[30rem]',
                className,
            )}
            aria-label="Welkom"
        >
            {hasImage && (
                <img
                    src={imageUrl ?? undefined}
                    alt=""
                    fetchPriority="high"
                    decoding="async"
                    onLoad={() => setLoaded(true)}
                    onError={() => setFailed(true)}
                    className={cn(
                        'absolute inset-0 size-full object-cover transition-opacity duration-500',
                        loaded ? 'opacity-100' : 'opacity-0',
                    )}
                />
            )}

            {!hasImage && (
                <span
                    aria-hidden
                    className="absolute -top-6 -right-6 text-[14rem] leading-none opacity-20 select-none sm:text-[20rem]"
                >
                    🍟
                </span>
            )}

            {/* Donkere plaat: leesbaarheid op foto, flat (geen gradient). */}
            <div
                className={cn(
                    'relative w-full px-4 pt-24 pb-8 sm:pb-12',
                    hasImage && 'bg-background/70',
                )}
            >
                <div className="mx-auto max-w-5xl motion-safe:animate-fade-up">
                    <p className="mb-3 font-display text-base font-extrabold tracking-[0.2em] text-primary uppercase">
                        Waregem · afhalen
                    </p>
                    <h1 className="max-w-3xl uppercase">{businessName}</h1>
                    <p className="mt-4 max-w-xl text-lg text-foreground/90 sm:text-xl">
                        {tagline}
                    </p>
                    <div className="mt-8">
                        {onCta ? (
                            <BigButton size="xl" onClick={onCta}>
                                {ctaLabel}
                                <ArrowDown aria-hidden />
                            </BigButton>
                        ) : (
                            <BigButton size="xl" asChild>
                                <a href={ctaHref}>
                                    {ctaLabel}
                                    <ArrowDown aria-hidden />
                                </a>
                            </BigButton>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
