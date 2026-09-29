import { Head, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/admin/settings';
import type { OpeningDayForm, SettingsForm } from '@/types/staff';

type Props = {
    settings: SettingsForm;
};

const DEFAULT_SLOT = { from: '11:30', to: '14:00' };
const DEFAULT_EVENING = { from: '17:00', to: '22:00' };

export default function SettingsPage({ settings }: Props) {
    const form = useForm<SettingsForm>(settings);
    const errors = form.errors as Record<string, string | undefined>;

    const setDay = (index: number, day: OpeningDayForm) => {
        const days = form.data.opening_hours.map((d, i) =>
            i === index ? day : d,
        );
        form.setData('opening_hours', days);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Instellingen" />
            <PageHeader
                title="Instellingen"
                description="Naam, adres, telefoon en openingsuren staan onderaan de site voor de klant."
            />

            <form
                onSubmit={submit}
                className="grid gap-6 lg:grid-cols-[1fr_1.4fr]"
            >
                <section className="flex flex-col gap-5 rounded-lg border border-border bg-card p-6">
                    <h2 className="text-lg uppercase">Gegevens</h2>
                    <FormField
                        label="Naam van de zaak"
                        value={form.data.business_name}
                        onChange={(e) =>
                            form.setData('business_name', e.target.value)
                        }
                        error={form.errors.business_name}
                        maxLength={60}
                        required
                    />
                    <FormField
                        label="Adres"
                        value={form.data.address}
                        onChange={(e) =>
                            form.setData('address', e.target.value)
                        }
                        error={form.errors.address}
                        maxLength={120}
                        required
                    />
                    <FormField
                        label="Telefoon"
                        value={form.data.phone}
                        onChange={(e) => form.setData('phone', e.target.value)}
                        error={form.errors.phone}
                        inputMode="tel"
                        maxLength={20}
                        required
                    />
                </section>

                <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-6">
                    <h2 className="text-lg uppercase">Openingsuren</h2>
                    <p className="text-sm text-muted-foreground">
                        Per dag maximaal twee blokken (bv. middag en avond).
                        Vink "gesloten" aan voor een sluitingsdag.
                    </p>
                    {errors.opening_hours && (
                        <p
                            role="alert"
                            className="text-sm font-bold text-primary"
                        >
                            {errors.opening_hours}
                        </p>
                    )}
                    <ul className="flex flex-col divide-y divide-border">
                        {form.data.opening_hours.map((day, di) => {
                            const closed = day.slots.length === 0;

                            return (
                                <li
                                    key={day.day}
                                    className="grid gap-3 py-3 sm:grid-cols-[7rem_1fr] sm:items-start"
                                >
                                    <div className="flex min-h-11 items-center justify-between sm:flex-col sm:items-start sm:justify-center sm:gap-1">
                                        <span className="text-base font-bold capitalize">
                                            {day.label}
                                        </span>
                                        <label className="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                                            <Checkbox
                                                className="size-5"
                                                checked={closed}
                                                onCheckedChange={(v) =>
                                                    setDay(di, {
                                                        ...day,
                                                        slots:
                                                            v === true
                                                                ? []
                                                                : [
                                                                      DEFAULT_SLOT,
                                                                      DEFAULT_EVENING,
                                                                  ],
                                                    })
                                                }
                                            />
                                            gesloten
                                        </label>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        {day.slots.map((slot, si) => {
                                            const fromError =
                                                errors[
                                                    `opening_hours.${di}.slots.${si}.from`
                                                ];
                                            const toError =
                                                errors[
                                                    `opening_hours.${di}.slots.${si}.to`
                                                ];

                                            return (
                                                <div
                                                    key={si}
                                                    className="flex flex-col gap-1"
                                                >
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Input
                                                            type="time"
                                                            value={slot.from}
                                                            aria-label={`${day.label} blok ${si + 1} van`}
                                                            aria-invalid={
                                                                fromError
                                                                    ? true
                                                                    : undefined
                                                            }
                                                            onChange={(e) =>
                                                                setDay(di, {
                                                                    ...day,
                                                                    slots: day.slots.map(
                                                                        (
                                                                            s,
                                                                            j,
                                                                        ) =>
                                                                            j ===
                                                                            si
                                                                                ? {
                                                                                      ...s,
                                                                                      from: e
                                                                                          .target
                                                                                          .value,
                                                                                  }
                                                                                : s,
                                                                    ),
                                                                })
                                                            }
                                                            className="h-11 w-32 bg-background text-base tabular-nums md:text-base"
                                                        />
                                                        <span className="text-muted-foreground">
                                                            tot
                                                        </span>
                                                        <Input
                                                            type="time"
                                                            value={slot.to}
                                                            aria-label={`${day.label} blok ${si + 1} tot`}
                                                            aria-invalid={
                                                                toError
                                                                    ? true
                                                                    : undefined
                                                            }
                                                            onChange={(e) =>
                                                                setDay(di, {
                                                                    ...day,
                                                                    slots: day.slots.map(
                                                                        (
                                                                            s,
                                                                            j,
                                                                        ) =>
                                                                            j ===
                                                                            si
                                                                                ? {
                                                                                      ...s,
                                                                                      to: e
                                                                                          .target
                                                                                          .value,
                                                                                  }
                                                                                : s,
                                                                    ),
                                                                })
                                                            }
                                                            className="h-11 w-32 bg-background text-base tabular-nums md:text-base"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-11"
                                                            aria-label={`${day.label} blok ${si + 1} verwijderen`}
                                                            onClick={() =>
                                                                setDay(di, {
                                                                    ...day,
                                                                    slots: day.slots.filter(
                                                                        (
                                                                            _,
                                                                            j,
                                                                        ) =>
                                                                            j !==
                                                                            si,
                                                                    ),
                                                                })
                                                            }
                                                        >
                                                            <X />
                                                        </Button>
                                                    </div>
                                                    {(fromError || toError) && (
                                                        <p
                                                            role="alert"
                                                            className="text-sm font-bold text-primary"
                                                        >
                                                            {fromError ??
                                                                toError}
                                                        </p>
                                                    )}
                                                </div>
                                            );
                                        })}
                                        {day.slots.length < 2 && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="h-10 w-fit"
                                                onClick={() =>
                                                    setDay(di, {
                                                        ...day,
                                                        slots: [
                                                            ...day.slots,
                                                            day.slots.length ===
                                                            0
                                                                ? DEFAULT_SLOT
                                                                : DEFAULT_EVENING,
                                                        ],
                                                    })
                                                }
                                            >
                                                <Plus />
                                                {closed
                                                    ? 'Uren toevoegen'
                                                    : 'Tweede blok'}
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </section>

                <div className="flex flex-wrap gap-3 lg:col-span-2">
                    <Button
                        type="submit"
                        className="h-11"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        Instellingen bewaren
                    </Button>
                </div>
            </form>
        </>
    );
}
