import { type ComponentProps, useId } from 'react';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<'input'>, 'id'> & {
    label: string;
    hint?: string;
    error?: string | null;
    /** Eigen id; anders automatisch. */
    id?: string;
    className?: string;
    inputClassName?: string;
};

/**
 * Groot formulierveld voor de gsm: label erboven, invoer 56 px hoog met grote tekst,
 * fout in accent onder het veld. Geef `inputMode`/`autoComplete` mee waar het past.
 */
export function Field({
    label,
    hint,
    error,
    id,
    className,
    inputClassName,
    ...props
}: Props) {
    const generated = useId();
    const inputId = id ?? generated;
    const hintId = `${inputId}-hint`;
    const errorId = `${inputId}-error`;

    return (
        <div className={cn('flex flex-col gap-2', className)}>
            <label htmlFor={inputId} className="text-lg font-bold">
                {label}
            </label>
            <input
                id={inputId}
                aria-invalid={error ? true : undefined}
                aria-describedby={
                    [hint ? hintId : null, error ? errorId : null]
                        .filter(Boolean)
                        .join(' ') || undefined
                }
                className={cn(
                    'h-14 w-full rounded-lg border-2 border-border bg-card px-4 text-xl text-foreground transition-colors placeholder:text-muted-foreground focus-visible:border-primary focus-visible:outline-none disabled:opacity-50',
                    error && 'border-primary',
                    inputClassName,
                )}
                {...props}
            />
            {hint && !error && (
                <p id={hintId} className="text-base text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={errorId}
                    role="alert"
                    className="text-base font-bold text-primary"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
