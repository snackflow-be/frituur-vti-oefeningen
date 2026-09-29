import { Link } from '@inertiajs/react';
import { BigButton, EmptyState } from '@/components/frituur';
import FrituurLayout from '@/layouts/frituur-layout';
import { home } from '@/routes';

type Props = { status: number; message?: string | null };

const TEXTS: Record<number, { icon: string; title: string; text: string }> = {
    401: {
        icon: '🔒',
        title: 'Hier moet je voor inloggen',
        text: 'Deze pagina is enkel voor het personeel van de frituur.',
    },
    403: {
        icon: '🚫',
        title: 'Dit mag je niet',
        text: 'Je hebt geen toegang tot deze pagina.',
    },
    404: {
        icon: '🔍',
        title: 'Pagina niet gevonden',
        text: 'Deze pagina bestaat niet (meer). Het menu staat gewoon klaar.',
    },
    419: {
        icon: '⏱️',
        title: 'Pagina verlopen',
        text: 'Je was even te lang weg. Laad de pagina opnieuw en probeer nog eens.',
    },
    429: {
        icon: '🐢',
        title: 'Even te druk',
        text: 'Te veel verzoeken na elkaar. Probeer over een minuut opnieuw.',
    },
    500: {
        icon: '🍟',
        title: 'Er ging iets mis',
        text: 'Onze fout, niet de jouwe. Probeer opnieuw of bestel aan de toog.',
    },
    503: {
        icon: '🔧',
        title: 'Even in onderhoud',
        text: 'We zijn zo terug. Probeer over een paar minuten opnieuw.',
    },
};

/** Nederlandse foutpagina (02-architect §3): de backend geeft `status` mee. */
export default function FoutPage({ status, message }: Props) {
    const known = TEXTS[status];
    const content = known ?? TEXTS[500];
    // Voor een onbekende status telt de Nederlandse boodschap van de backend.
    const text = !known && message?.trim() ? message : content.text;

    return (
        <FrituurLayout title={content.title} reserveCartBar={false}>
            <EmptyState
                icon={content.icon}
                title={content.title}
                text={text}
                size="lg"
                action={
                    <BigButton asChild size="xl">
                        <Link href={home.url()}>Naar het menu</Link>
                    </BigButton>
                }
                className="my-10"
            />
            <p className="pb-10 text-center text-sm text-muted-foreground tabular-nums">
                Foutcode {status}
            </p>
        </FrituurLayout>
    );
}
