import { type ComponentProps, useId } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<typeof Input>, 'id'> & {
    label: string;
    error?: string;
    hint?: string;
    id?: string;
    className?: string;
};

/** Label + invoer (44 px hoog) + foutmelding in accent, voor de adminformulieren. */
export function FormField({
    label,
    error,
    hint,
    id,
    className,
    ...props
}: Props) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={inputId} className="text-base">
                {label}
            </Label>
            <Input
                id={inputId}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${inputId}-error` : undefined}
                className="h-11 bg-card text-base md:text-base"
                {...props}
            />
            {hint && !error && (
                <p className="text-sm text-muted-foreground">{hint}</p>
            )}
            {error && (
                <p
                    id={`${inputId}-error`}
                    role="alert"
                    className="text-sm font-bold text-primary"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

/** Zelfde stijl voor een meerregelig veld (omschrijving, boodschap). */
export function FormTextarea({
    label,
    error,
    hint,
    id,
    className,
    ...props
}: Omit<ComponentProps<'textarea'>, 'id'> & {
    label: string;
    error?: string;
    hint?: string;
    id?: string;
}) {
    const generated = useId();
    const inputId = id ?? generated;

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={inputId} className="text-base">
                {label}
            </Label>
            <textarea
                id={inputId}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${inputId}-error` : undefined}
                className={cn(
                    'w-full rounded-md border border-input bg-card px-3 py-2 text-base text-foreground outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive',
                )}
                {...props}
            />
            {hint && !error && (
                <p className="text-sm text-muted-foreground">{hint}</p>
            )}
            {error && (
                <p
                    id={`${inputId}-error`}
                    role="alert"
                    className="text-sm font-bold text-primary"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
