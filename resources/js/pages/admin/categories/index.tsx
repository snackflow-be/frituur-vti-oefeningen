import { Head, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormField } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { EmptyState } from '@/components/frituur';
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
import { destroy, move, store, update } from '@/routes/admin/categories';
import type { AdminCategory } from '@/types/staff';

type Props = {
    categories: AdminCategory[];
};

type Editing = { mode: 'create' } | { mode: 'edit'; category: AdminCategory };

export default function CategoriesIndex({ categories }: Props) {
    const [editing, setEditing] = useState<Editing | null>(null);
    const [toDelete, setToDelete] = useState<AdminCategory | null>(null);
    const [busy, setBusy] = useState(false);
    const form = useForm({ name: '' });

    const open = (next: Editing) => {
        form.clearErrors();
        form.setData('name', next.mode === 'edit' ? next.category.name : '');
        setEditing(next);
    };

    const close = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!editing) {
            return;
        }

        const options = { preserveScroll: true, onSuccess: close };

        if (editing.mode === 'edit') {
            form.put(update.url(editing.category.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    const moveCategory = (category: AdminCategory, direction: 'up' | 'down') =>
        router.patch(
            move.url(category.id),
            { direction },
            { preserveScroll: true, preserveState: true },
        );

    const confirmDelete = () => {
        if (!toDelete) {
            return;
        }

        setBusy(true);
        router.delete(destroy.url(toDelete.id), {
            preserveScroll: true,
            onError: (errors) => {
                toast.error(errors.name ?? 'Verwijderen lukte niet.');
            },
            onFinish: () => {
                setBusy(false);
                setToDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Categorieën" />
            <PageHeader
                title="Categorieën"
                description="De volgorde hier is de volgorde op de site. Een categorie met producten kan niet weg."
                actions={
                    <Button
                        className="h-11"
                        onClick={() => open({ mode: 'create' })}
                    >
                        <Plus />
                        Nieuwe categorie
                    </Button>
                }
            />

            {categories.length === 0 ? (
                <EmptyState
                    icon="🗂️"
                    title="Nog geen categorieën"
                    text="Maak eerst een categorie, dan kun je producten toevoegen."
                    action={
                        <Button
                            className="h-11"
                            onClick={() => open({ mode: 'create' })}
                        >
                            <Plus />
                            Nieuwe categorie
                        </Button>
                    }
                />
            ) : (
                <div className="rounded-lg border border-border bg-card">
                    <Table className="text-base">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Naam</TableHead>
                                <TableHead className="text-right">
                                    Producten
                                </TableHead>
                                <TableHead className="text-center">
                                    Volgorde
                                </TableHead>
                                <TableHead className="text-right">
                                    Acties
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.map((category, i) => (
                                <TableRow key={category.id}>
                                    <TableCell className="font-bold">
                                        {category.name}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {category.products_count ?? 0}
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <div className="inline-flex gap-1">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11"
                                                disabled={i === 0}
                                                aria-label={`${category.name} omhoog`}
                                                onClick={() =>
                                                    moveCategory(category, 'up')
                                                }
                                            >
                                                <ArrowUp />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11"
                                                disabled={
                                                    i === categories.length - 1
                                                }
                                                aria-label={`${category.name} omlaag`}
                                                onClick={() =>
                                                    moveCategory(
                                                        category,
                                                        'down',
                                                    )
                                                }
                                            >
                                                <ArrowDown />
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="inline-flex gap-1">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11"
                                                aria-label={`${category.name} hernoemen`}
                                                onClick={() =>
                                                    open({
                                                        mode: 'edit',
                                                        category,
                                                    })
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                className="size-11 text-primary hover:text-primary"
                                                aria-label={`${category.name} verwijderen`}
                                                disabled={
                                                    (category.products_count ??
                                                        0) > 0
                                                }
                                                title={
                                                    (category.products_count ??
                                                        0) > 0
                                                        ? 'Verplaats of verwijder eerst de producten in deze categorie.'
                                                        : undefined
                                                }
                                                onClick={() =>
                                                    setToDelete(category)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}

            <Dialog open={editing !== null} onOpenChange={(o) => !o && close()}>
                <DialogContent className="bg-card">
                    <form onSubmit={submit} className="flex flex-col gap-5">
                        <DialogHeader>
                            <DialogTitle className="font-display text-xl font-extrabold uppercase">
                                {editing?.mode === 'edit'
                                    ? 'Categorie hernoemen'
                                    : 'Nieuwe categorie'}
                            </DialogTitle>
                            <DialogDescription className="text-base">
                                Bv. Frieten, Snacks, Sauzen, Dranken.
                            </DialogDescription>
                        </DialogHeader>
                        <FormField
                            label="Naam"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            error={form.errors.name}
                            maxLength={40}
                            required
                            autoFocus
                        />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11"
                                onClick={close}
                            >
                                Annuleren
                            </Button>
                            <Button
                                type="submit"
                                className="h-11"
                                disabled={form.processing}
                            >
                                {form.processing && <Spinner />}
                                {editing?.mode === 'edit'
                                    ? 'Bewaren'
                                    : 'Aanmaken'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={toDelete !== null}
                onOpenChange={(o) => !o && setToDelete(null)}
                title="Categorie verwijderen?"
                description={
                    toDelete
                        ? `"${toDelete.name}" verdwijnt van de site.`
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
