import type { ReactNode } from 'react';

type Props = {
    title: string;
    description?: string;
    /** Knop(pen) rechts, bv. "Nieuw product". */
    actions?: ReactNode;
};

/** Kop van elke adminpagina: titel in Archivo, korte uitleg, acties rechts. */
export function PageHeader({ title, description, actions }: Props) {
    return (
        <header className="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 className="text-[2rem] leading-none">{title}</h1>
                {description && (
                    <p className="mt-2 text-base text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </header>
    );
}
