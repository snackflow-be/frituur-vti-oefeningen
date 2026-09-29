import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    destroy,
    password as passwordRoute,
    store,
} from '@/routes/admin/staff';
import type { StaffMember } from '@/types/staff';

type Props = {
    staff: StaffMember[];
    currentUserId: number | null;
};

export default function StaffIndex({ staff, currentUserId }: Props) {
    const [inviting, setInviting] = useState(false);
    const [resetting, setResetting] = useState<StaffMember | null>(null);
    const [toDelete, setToDelete] = useState<StaffMember | null>(null);
    const [busy, setBusy] = useState(false);

    const invite = useForm({ name: '', email: '', password: '' });
    const reset = useForm({ password: '' });

    const submitInvite = (event: FormEvent) => {
        event.preventDefault();
        invite.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                setInviting(false);
                invite.reset();
            },
        });
    };

    const submitReset = (event: FormEvent) => {
        event.preventDefault();

        if (!resetting) {
            return;
        }

        reset.put(passwordRoute.url(resetting.id), {
            preserveScroll: true,
            onSuccess: () => {
                setResetting(null);
                reset.reset();
            },
        });
    };

    const confirmDelete = () => {
        if (!toDelete) {
            return;
        }

        setBusy(true);
        router.delete(destroy.url(toDelete.id), {
            preserveScroll: true,
            onError: (errors) =>
                toast.error(errors.staff ?? 'Verwijderen lukte niet.'),
            onFinish: () => {
                setBusy(false);
                setToDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Personeel" />
            <PageHeader
                title="Personeel"
                description="Iedereen hier kan inloggen op de keuken en het beheer. Geef het tijdelijke wachtwoord mondeling door; wie inlogt, kan het zelf wijzigen onder 'Mijn account'."
                actions={
                    <Button
                        className="h-11"
                        onClick={() => {
                            invite.clearErrors();
                            setInviting(true);
                        }}
                    >
                        <Plus />
                        Personeel toevoegen
                    </Button>
                }
            />

            <div className="rounded-lg border border-border bg-card">
                <Table className="text-base">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Naam</TableHead>
                            <TableHead>E-mail</TableHead>
                            <TableHead className="text-right">Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {staff.map((member) => {
                            const isMe = member.id === currentUserId;

                            return (
                                <TableRow key={member.id}>
                                    <TableCell className="font-bold">
                                        {member.name}
                                        {isMe && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                jij
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>{member.email}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="inline-flex gap-1">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11"
                                                aria-label={`Nieuw wachtwoord voor ${member.name}`}
                                                onClick={() => {
                                                    reset.clearErrors();
                                                    reset.reset();
                                                    setResetting(member);
                                                }}
                                            >
                                                <KeyRound />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11 text-primary hover:text-primary"
                                                aria-label={`${member.name} verwijderen`}
                                                disabled={
                                                    isMe || staff.length <= 1
                                                }
                                                title={
                                                    isMe
                                                        ? 'Je kunt jezelf niet verwijderen.'
                                                        : undefined
                                                }
                                                onClick={() =>
                                                    setToDelete(member)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>

            <Dialog open={inviting} onOpenChange={setInviting}>
                <DialogContent className="bg-card">
                    <form
                        onSubmit={submitInvite}
                        className="flex flex-col gap-5"
                    >
                        <DialogHeader>
                            <DialogTitle className="font-display text-xl font-extrabold uppercase">
                                Personeel toevoegen
                            </DialogTitle>
                            <DialogDescription className="text-base">
                                Kies een tijdelijk wachtwoord van minstens 8
                                tekens.
                            </DialogDescription>
                        </DialogHeader>
                        <FormField
                            label="Naam"
                            value={invite.data.name}
                            onChange={(e) =>
                                invite.setData('name', e.target.value)
                            }
                            error={invite.errors.name}
                            required
                            autoFocus
                        />
                        <FormField
                            label="E-mailadres"
                            type="email"
                            value={invite.data.email}
                            onChange={(e) =>
                                invite.setData('email', e.target.value)
                            }
                            error={invite.errors.email}
                            autoComplete="off"
                            required
                        />
                        <FormField
                            label="Tijdelijk wachtwoord"
                            type="text"
                            value={invite.data.password}
                            onChange={(e) =>
                                invite.setData('password', e.target.value)
                            }
                            error={invite.errors.password}
                            autoComplete="off"
                            minLength={8}
                            required
                        />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11"
                                onClick={() => setInviting(false)}
                            >
                                Annuleren
                            </Button>
                            <Button
                                type="submit"
                                className="h-11"
                                disabled={invite.processing}
                            >
                                {invite.processing && <Spinner />}
                                Toevoegen
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={resetting !== null}
                onOpenChange={(o) => !o && setResetting(null)}
            >
                <DialogContent className="bg-card">
                    <form
                        onSubmit={submitReset}
                        className="flex flex-col gap-5"
                    >
                        <DialogHeader>
                            <DialogTitle className="font-display text-xl font-extrabold uppercase">
                                Nieuw wachtwoord
                            </DialogTitle>
                            <DialogDescription className="text-base">
                                Voor {resetting?.name}. Minstens 8 tekens.
                            </DialogDescription>
                        </DialogHeader>
                        <FormField
                            label="Nieuw wachtwoord"
                            type="text"
                            value={reset.data.password}
                            onChange={(e) =>
                                reset.setData('password', e.target.value)
                            }
                            error={reset.errors.password}
                            autoComplete="off"
                            minLength={8}
                            required
                            autoFocus
                        />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11"
                                onClick={() => setResetting(null)}
                            >
                                Annuleren
                            </Button>
                            <Button
                                type="submit"
                                className="h-11"
                                disabled={reset.processing}
                            >
                                {reset.processing && <Spinner />}
                                Bewaren
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={toDelete !== null}
                onOpenChange={(o) => !o && setToDelete(null)}
                title="Personeelslid verwijderen?"
                description={
                    toDelete
                        ? `${toDelete.name} kan daarna niet meer inloggen.`
                        : undefined
                }
                confirmLabel="Ja, verwijderen"
                destructive
                processing={busy}
                onConfirm={confirmDelete}
            />
        </>
    );
}
