import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { EmptyState, Price, ProductImage } from '@/components/frituur';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, destroy, edit, move, toggle } from '@/routes/admin/products';
import type { AdminCategoryWithProducts, AdminProduct } from '@/types/staff';

type Props = {
    categories: AdminCategoryWithProducts[];
};

const ALL = 'all';

export default function ProductsIndex({ categories }: Props) {
    const [query, setQuery] = useState('');
    const [categoryId, setCategoryId] = useState<string>(ALL);
    const [toDelete, setToDelete] = useState<AdminProduct | null>(null);
    const [busy, setBusy] = useState(false);

    const filtered = useMemo(() => {
        const term = query.trim().toLowerCase();

        return categories
            .filter((c) => categoryId === ALL || String(c.id) === categoryId)
            .map((c) => ({
                ...c,
                products: c.products.filter(
                    (p) =>
                        term === '' ||
                        p.name.toLowerCase().includes(term) ||
                        (p.description ?? '').toLowerCase().includes(term),
                ),
            }))
            .filter((c) => c.products.length > 0 || term === '');
    }, [categories, query, categoryId]);

    const total = categories.reduce((n, c) => n + c.products.length, 0);

    const setFlag = (
        product: AdminProduct,
        flag: 'is_visible' | 'is_sold_out',
        value: boolean,
    ) =>
        router.patch(
            toggle.url(product.id),
            { [flag]: value },
            { preserveScroll: true, preserveState: true },
        );

    const moveProduct = (product: AdminProduct, direction: 'up' | 'down') =>
        router.patch(
            move.url(product.id),
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
            onFinish: () => {
                setBusy(false);
                setToDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Producten" />
            <PageHeader
                title="Producten"
                description={`${total} producten. Volgorde per categorie met de pijltjes; verborgen producten staan niet op de site.`}
                actions={
                    <Button className="h-11" asChild>
                        <Link href={create()}>
                            <Plus />
                            Nieuw product
                        </Link>
                    </Button>
                }
            />

            <div className="grid gap-4 rounded-lg border border-border bg-card p-4 sm:grid-cols-[1fr_auto] sm:items-end">
                <div className="grid gap-2">
                    <Label htmlFor="search" className="text-base">
                        Zoeken
                    </Label>
                    <div className="relative">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Naam of omschrijving"
                            className="h-11 pl-10 text-base md:text-base"
                        />
                    </div>
                </div>
                <div className="grid gap-2">
                    <Label className="text-base">Categorie</Label>
                    <Select value={categoryId} onValueChange={setCategoryId}>
                        <SelectTrigger className="h-11! min-w-48 bg-background text-base">
                            <SelectValue placeholder="Alle categorieën" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>
                                Alle categorieën
                            </SelectItem>
                            {categories.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {filtered.length === 0 ? (
                <EmptyState
                    icon="🍟"
                    title="Geen producten gevonden"
                    text={
                        total === 0
                            ? 'Voeg je eerste product toe.'
                            : 'Pas je zoekterm of categorie aan.'
                    }
                    action={
                        total === 0 ? (
                            <Button className="h-11" asChild>
                                <Link href={create()}>
                                    <Plus />
                                    Nieuw product
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />
            ) : (
                filtered.map((category) => (
                    <section
                        key={category.id}
                        className="rounded-lg border border-border bg-card"
                    >
                        <h2 className="border-b border-border px-4 py-3 text-lg uppercase">
                            {category.name}
                            <span className="ml-2 text-sm font-normal text-muted-foreground normal-case">
                                {category.products.length} producten
                            </span>
                        </h2>
                        {category.products.length === 0 ? (
                            <p className="px-4 py-5 text-base text-muted-foreground">
                                Nog geen producten in deze categorie.
                            </p>
                        ) : (
                            <Table className="text-base">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-16">
                                            Foto
                                        </TableHead>
                                        <TableHead>Naam</TableHead>
                                        <TableHead className="text-right">
                                            Prijs
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Zichtbaar
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Uitverkocht
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
                                    {category.products.map((product, i) => (
                                        <TableRow key={product.id}>
                                            <TableCell>
                                                <div className="w-12">
                                                    <ProductImage
                                                        src={product.image_url}
                                                        alt={product.name}
                                                        categorySlug={
                                                            category.slug
                                                        }
                                                        ratio="square"
                                                        size="sm"
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                <p className="font-bold">
                                                    {product.name}
                                                </p>
                                                {product.description && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {product.description}
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Price
                                                    cents={product.price_cents}
                                                    size="sm"
                                                />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Checkbox
                                                    className="size-6"
                                                    checked={product.is_visible}
                                                    aria-label={`${product.name} zichtbaar op de site`}
                                                    onCheckedChange={(v) =>
                                                        setFlag(
                                                            product,
                                                            'is_visible',
                                                            v === true,
                                                        )
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Checkbox
                                                    className="size-6"
                                                    checked={
                                                        product.is_sold_out
                                                    }
                                                    aria-label={`${product.name} uitverkocht`}
                                                    onCheckedChange={(v) =>
                                                        setFlag(
                                                            product,
                                                            'is_sold_out',
                                                            v === true,
                                                        )
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="inline-flex gap-1">
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-11"
                                                        disabled={i === 0}
                                                        aria-label={`${product.name} omhoog`}
                                                        onClick={() =>
                                                            moveProduct(
                                                                product,
                                                                'up',
                                                            )
                                                        }
                                                    >
                                                        <ArrowUp />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-11"
                                                        disabled={
                                                            i ===
                                                            category.products
                                                                .length -
                                                                1
                                                        }
                                                        aria-label={`${product.name} omlaag`}
                                                        onClick={() =>
                                                            moveProduct(
                                                                product,
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
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(
                                                                product.id,
                                                            )}
                                                            aria-label={`${product.name} bewerken`}
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-11 text-primary hover:text-primary"
                                                        aria-label={`${product.name} verwijderen`}
                                                        onClick={() =>
                                                            setToDelete(product)
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
                        )}
                    </section>
                ))
            )}

            <ConfirmDialog
                open={toDelete !== null}
                onOpenChange={(open) => !open && setToDelete(null)}
                title="Product verwijderen?"
                description={
                    toDelete
                        ? `"${toDelete.name}" verdwijnt van de site. Oude bestellingen blijven kloppen.`
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
