import { Head, useForm } from '@inertiajs/react';
import { DoorClosed, DoorOpen } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormTextarea } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { update } from '@/routes/admin/ordering';
import type { Ordering } from '@/types/frituur';

type Props = {
    ordering: Ordering;
};

export default function OrderingPage({ ordering }: Props) {
    const form = useForm({
        is_open: ordering.is_open,
        closed_message: ordering.closed_message ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Bestellen open/dicht" />
            <PageHeader
                title="Bestellen open/dicht"
                description="Sluit het bestellen tijdelijk, bv. bij een feestdag of als het te druk is. Het menu blijft zichtbaar."
            />

            <form
                onSubmit={submit}
                className="grid gap-6 lg:grid-cols-[1fr_1fr]"
            >
                <section
                    className={cn(
                        'flex flex-col justify-between gap-6 rounded-lg border-2 p-6',
                        form.data.is_open
                            ? 'border-status-ready bg-card'
                            : 'border-primary bg-primary text-primary-foreground',
                    )}
                >
                    <div>
                        <p className="font-display text-sm tracking-wide uppercase opacity-80">
                            Nu
                        </p>
                        <p className="mt-2 font-display text-4xl leading-none font-extrabold uppercase">
                            {form.data.is_open
                                ? 'Bestellen open'
                                : 'Bestellen gesloten'}
                        </p>
                        <p className="mt-3 text-base">
                            {form.data.is_open
                                ? 'Klanten kunnen bestellingen doorsturen.'
                                : 'Klanten zien je boodschap en kunnen niets doorsturen.'}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant={form.data.is_open ? 'destructive' : 'outline'}
                        className={cn(
                            'h-12 text-base',
                            !form.data.is_open &&
                                'border-primary-foreground bg-transparent text-primary-foreground hover:bg-primary-foreground hover:text-primary',
                        )}
                        onClick={() =>
                            form.setData('is_open', !form.data.is_open)
                        }
                    >
                        {form.data.is_open ? <DoorClosed /> : <DoorOpen />}
                        {form.data.is_open
                            ? 'Bestellen sluiten'
                            : 'Bestellen openen'}
                    </Button>
                </section>

                <section className="flex flex-col gap-5 rounded-lg border border-border bg-card p-6">
                    <FormTextarea
                        label="Boodschap die de klant ziet als het gesloten is"
                        value={form.data.closed_message}
                        onChange={(e) =>
                            form.setData('closed_message', e.target.value)
                        }
                        error={form.errors.closed_message}
                        hint={`${form.data.closed_message.length}/140 tekens. Blijft bewaard, ook als je weer opent.`}
                        maxLength={140}
                        rows={3}
                        placeholder='Bv. "Vandaag gesloten" of "Te druk, bel ons op 056 00 00 00"'
                    />
                    <div className="flex flex-wrap gap-3 border-t border-border pt-5">
                        <Button
                            type="submit"
                            className="h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Bewaren
                        </Button>
                    </div>
                </section>
            </form>
        </>
    );
}
