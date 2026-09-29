import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

export const bigButtonVariants = cva(
    'inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg font-sans font-bold whitespace-nowrap transition-colors select-none disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                primary:
                    'bg-primary text-primary-foreground hover:bg-primary-hover',
                secondary:
                    'border-2 border-border bg-card text-foreground hover:border-foreground',
                ghost: 'text-foreground hover:bg-card',
                danger: 'border-2 border-primary bg-transparent text-primary hover:bg-primary hover:text-primary-foreground',
            },
            size: {
                md: 'h-12 px-5 text-base [&_svg]:size-5',
                lg: 'h-14 px-6 text-lg [&_svg]:size-6',
                xl: 'h-16 px-8 text-xl [&_svg]:size-7',
                icon: 'size-12 [&_svg]:size-6',
            },
            block: {
                true: 'flex w-full',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'lg',
        },
    },
);

type Props = ComponentProps<'button'> &
    VariantProps<typeof bigButtonVariants> & {
        asChild?: boolean;
        loading?: boolean;
    };

/**
 * Primaire en secundaire knop voor klant en keuken. Minstens 48 px hoog.
 * `asChild` om een Inertia <Link> als knop te tonen.
 */
export function BigButton({
    className,
    variant,
    size,
    block,
    asChild = false,
    loading = false,
    disabled,
    children,
    ...props
}: Props) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="big-button"
            className={cn(
                bigButtonVariants({ variant, size, block }),
                className,
            )}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        >
            {/* Radix Slot wil precies één kind: bij asChild geen spinner ernaast. */}
            {asChild ? (
                children
            ) : (
                <>
                    {loading ? <Spinner aria-hidden /> : null}
                    {children}
                </>
            )}
        </Comp>
    );
}
