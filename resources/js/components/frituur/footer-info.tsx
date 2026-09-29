import { Clock, MapPin, Phone } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Business } from '@/types/frituur';

type Props = {
    business: Business;
    className?: string;
};

function formatSlots(slots: Business['opening_hours'][number]['slots']) {
    if (slots.length === 0) {
        return 'gesloten';
    }

    return slots.map((slot) => `${slot.from} – ${slot.to}`).join(' en ');
}

/** Footer met naam, adres, telefoon (klikbaar) en openingsuren per dag. */
export function FooterInfo({ business, className }: Props) {
    const phoneHref = `tel:${business.phone.replace(/[^\d+]/g, '')}`;

    return (
        <div
            className={cn(
                'grid gap-8 text-base text-muted-foreground sm:grid-cols-2',
                className,
            )}
        >
            <div>
                <h2 className="text-foreground uppercase">{business.name}</h2>
                <p className="mt-4 flex items-start gap-3">
                    <MapPin className="mt-0.5 size-5 shrink-0" aria-hidden />
                    <span>{business.address}</span>
                </p>
                <p className="mt-3 flex items-start gap-3">
                    <Phone className="mt-0.5 size-5 shrink-0" aria-hidden />
                    <a
                        href={phoneHref}
                        className="inline-flex min-h-11 items-center text-lg font-bold text-foreground underline-offset-4 hover:text-primary hover:underline"
                    >
                        {business.phone}
                    </a>
                </p>
                <p className="mt-6 text-sm">
                    Je betaalt bij afhaling (cash of kaart).
                </p>
            </div>

            <div>
                <h3 className="flex items-center gap-2 text-foreground uppercase">
                    <Clock className="size-5" aria-hidden />
                    Openingsuren
                </h3>
                <dl className="mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-1.5">
                    {business.opening_hours.map((day) => (
                        <div key={day.day} className="contents">
                            <dt className="font-bold text-foreground capitalize">
                                {day.label}
                            </dt>
                            <dd className="tabular-nums">
                                {formatSlots(day.slots)}
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
        </div>
    );
}
