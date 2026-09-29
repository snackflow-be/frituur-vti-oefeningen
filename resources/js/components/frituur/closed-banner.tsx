import { AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    message: string | null;
    className?: string;
};

/** Opvallende balk als het bestellen gesloten is. Toont de boodschap van de baas. */
export function ClosedBanner({ message, className }: Props) {
    return (
        <div
            role="status"
            className={cn(
                '-mx-4 flex items-start gap-3 bg-primary px-4 py-4 text-primary-foreground motion-safe:animate-fade-in',
                className,
            )}
        >
            <AlertTriangle className="mt-0.5 size-6 shrink-0" aria-hidden />
            <div>
                <p className="font-display text-lg leading-tight font-extrabold uppercase">
                    Bestellen is nu gesloten
                </p>
                {message && <p className="mt-1 text-base">{message}</p>}
            </div>
        </div>
    );
}
