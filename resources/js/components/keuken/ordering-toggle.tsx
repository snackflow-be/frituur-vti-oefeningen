import { DoorClosed, DoorOpen } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { BigButton } from '@/components/frituur';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { ApiError, apiPatch } from '@/lib/api';
import { ordering as orderingRoute } from '@/routes/keuken';
import type { Ordering } from '@/types/frituur';

type Props = {
    ordering: Ordering;
    onChange: (ordering: Ordering) => void;
};

/**
 * Bestellen open/dicht vanuit de keuken, met bevestiging. Sluiten vraagt een boodschap voor
 * de klant (vooraf ingevuld met de vorige).
 */
export function OrderingToggle({ ordering, onChange }: Props) {
    const [open, setOpen] = useState(false);
    const [message, setMessage] = useState(ordering.closed_message ?? '');
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const closing = ordering.is_open;

    const submit = async () => {
        setBusy(true);
        setError(null);

        try {
            const data = await apiPatch<{ ordering: Ordering }>(
                orderingRoute.url(),
                closing
                    ? { is_open: false, closed_message: message }
                    : { is_open: true },
            );

            onChange(data.ordering);
            setOpen(false);
            toast.success(
                data.ordering.is_open
                    ? 'Bestellen staat weer open.'
                    : 'Bestellen is gesloten; klanten zien je boodschap.',
            );
        } catch (caught) {
            if (caught instanceof ApiError) {
                setError(caught.fieldError('closed_message') ?? caught.message);
            } else {
                setError('Er ging iets mis. Probeer opnieuw.');
            }
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next) {
                    setMessage(ordering.closed_message ?? '');
                    setError(null);
                }
            }}
        >
            <DialogTrigger asChild>
                <BigButton variant={closing ? 'secondary' : 'danger'} size="md">
                    {closing ? (
                        <DoorClosed aria-hidden />
                    ) : (
                        <DoorOpen aria-hidden />
                    )}
                    {closing ? 'Bestellen sluiten' : 'Bestellen openen'}
                </BigButton>
            </DialogTrigger>
            <DialogContent className="border-border bg-card sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="font-display text-2xl font-extrabold uppercase">
                        {closing ? 'Bestellen sluiten?' : 'Bestellen openen?'}
                    </DialogTitle>
                    <DialogDescription className="text-base">
                        {closing
                            ? 'Klanten kunnen dan niets meer doorsturen tot je het weer opent. Ze zien deze boodschap:'
                            : 'Klanten kunnen dan meteen weer bestellen.'}
                    </DialogDescription>
                </DialogHeader>

                {closing && (
                    <div className="flex flex-col gap-2">
                        <label
                            htmlFor="keuken-closed-message"
                            className="text-base font-bold"
                        >
                            Boodschap voor de klant
                        </label>
                        <textarea
                            id="keuken-closed-message"
                            value={message}
                            onChange={(event) => setMessage(event.target.value)}
                            maxLength={140}
                            rows={3}
                            placeholder='Bv. "Te druk, bel ons op 056 00 00 00"'
                            aria-invalid={error ? true : undefined}
                            className="w-full rounded-lg border-2 border-border bg-background px-4 py-3 text-lg text-foreground focus-visible:border-primary focus-visible:outline-none"
                        />
                        <p className="text-sm text-muted-foreground tabular-nums">
                            {message.length}/140
                        </p>
                    </div>
                )}

                {error && (
                    <p role="alert" className="font-bold text-primary">
                        {error}
                    </p>
                )}

                <DialogFooter>
                    <BigButton
                        variant="ghost"
                        size="md"
                        onClick={() => setOpen(false)}
                    >
                        Annuleren
                    </BigButton>
                    <BigButton
                        size="md"
                        variant={closing ? 'danger' : 'primary'}
                        loading={busy}
                        onClick={() => void submit()}
                    >
                        {closing ? 'Ja, sluiten' : 'Ja, openen'}
                    </BigButton>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
