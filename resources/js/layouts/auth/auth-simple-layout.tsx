import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/** Loginschil in de huisstijl: woordmerk, grote titel, formulier in een kaart. */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-md">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4 text-center">
                        <Link
                            href={home()}
                            className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase"
                        >
                            <span className="text-primary">Frituur</span> VTI
                        </Link>

                        <div className="space-y-2">
                            <h1 className="text-[2.25rem] leading-none">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-base text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-6">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
