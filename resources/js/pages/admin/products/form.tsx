import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, X } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import { FormField, FormTextarea } from '@/components/admin/form-field';
import { PageHeader } from '@/components/admin/page-header';
import { ProductImage } from '@/components/frituur';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/admin/products';
import type { AdminCategory, AdminProduct } from '@/types/staff';

type Props = {
    product: AdminProduct | null;
    categories: AdminCategory[];
};

type FormData = {
    name: string;
    description: string;
    price: string;
    category_id: string;
    image: File | null;
    remove_image: boolean;
    is_visible: boolean;
    is_sold_out: boolean;
    _method?: string;
};

/** 350 → "3,50" voor het prijsveld. */
function centsToInput(cents: number): string {
    return `${Math.floor(cents / 100)},${(cents % 100).toString().padStart(2, '0')}`;
}

export default function ProductForm({ product, categories }: Props) {
    const editing = product !== null;
    const form = useForm<FormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        price: product ? centsToInput(product.price_cents) : '',
        category_id: product
            ? String(product.category_id)
            : categories[0]
              ? String(categories[0].id)
              : '',
        image: null,
        remove_image: false,
        is_visible: product?.is_visible ?? true,
        is_sold_out: product?.is_sold_out ?? false,
    });
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!form.data.image) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(form.data.image);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [form.data.image]);

    const categorySlug = categories.find(
        (c) => String(c.id) === form.data.category_id,
    )?.slug;
    const currentImage = form.data.remove_image
        ? null
        : (product?.image_url ?? null);
    const shownImage = preview ?? currentImage;

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (editing) {
            // Bestanden gaan niet mee met PUT: POST met _method=PUT (Inertia-conventie).
            form.transform((data) => ({ ...data, _method: 'PUT' }));
            form.post(update.url(product.id), { forceFormData: true });
        } else {
            form.post(store.url(), { forceFormData: true });
        }
    };

    return (
        <>
            <Head
                title={editing ? `${product.name} bewerken` : 'Nieuw product'}
            />
            <PageHeader
                title={editing ? product.name : 'Nieuw product'}
                description="Prijs in euro met komma, bv. 3,50. Foto: jpg, png of webp, maximaal 2 MB."
                actions={
                    <Button variant="outline" className="h-11" asChild>
                        <Link href={index()}>
                            <ArrowLeft />
                            Alle producten
                        </Link>
                    </Button>
                }
            />

            <form
                onSubmit={submit}
                className="grid gap-6 lg:grid-cols-[2fr_1fr]"
                encType="multipart/form-data"
            >
                <div className="flex flex-col gap-5 rounded-lg border border-border bg-card p-5">
                    <FormField
                        label="Naam"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        error={form.errors.name}
                        maxLength={60}
                        required
                        autoFocus
                    />
                    <FormTextarea
                        label="Korte omschrijving"
                        value={form.data.description}
                        onChange={(e) =>
                            form.setData('description', e.target.value)
                        }
                        error={form.errors.description}
                        hint={`${form.data.description.length}/60 tekens`}
                        maxLength={60}
                        rows={2}
                    />
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            label="Prijs (€)"
                            value={form.data.price}
                            onChange={(e) =>
                                form.setData('price', e.target.value)
                            }
                            error={form.errors.price}
                            inputMode="decimal"
                            placeholder="3,50"
                            required
                        />
                        <div className="grid gap-2">
                            <Label className="text-base">Categorie</Label>
                            <Select
                                value={form.data.category_id}
                                onValueChange={(v) =>
                                    form.setData('category_id', v)
                                }
                            >
                                <SelectTrigger
                                    className="h-11! w-full bg-background text-base"
                                    aria-invalid={
                                        form.errors.category_id
                                            ? true
                                            : undefined
                                    }
                                >
                                    <SelectValue placeholder="Kies een categorie" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {form.errors.category_id && (
                                <p
                                    role="alert"
                                    className="text-sm font-bold text-primary"
                                >
                                    {form.errors.category_id}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-6 pt-2">
                        <label className="flex min-h-11 cursor-pointer items-center gap-3 text-base">
                            <Checkbox
                                className="size-6"
                                checked={form.data.is_visible}
                                onCheckedChange={(v) =>
                                    form.setData('is_visible', v === true)
                                }
                            />
                            Zichtbaar op de site
                        </label>
                        <label className="flex min-h-11 cursor-pointer items-center gap-3 text-base">
                            <Checkbox
                                className="size-6"
                                checked={form.data.is_sold_out}
                                onCheckedChange={(v) =>
                                    form.setData('is_sold_out', v === true)
                                }
                            />
                            Uitverkocht
                        </label>
                    </div>

                    <div className="flex flex-wrap gap-3 border-t border-border pt-5">
                        <Button
                            type="submit"
                            className="h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {editing
                                ? 'Wijzigingen bewaren'
                                : 'Product aanmaken'}
                        </Button>
                        <Button variant="outline" className="h-11" asChild>
                            <Link href={index()}>Annuleren</Link>
                        </Button>
                    </div>
                </div>

                <aside className="flex flex-col gap-4 rounded-lg border border-border bg-card p-5">
                    <h2 className="text-lg uppercase">Foto</h2>
                    <ProductImage
                        src={shownImage}
                        alt={form.data.name || 'Productfoto'}
                        categorySlug={categorySlug}
                    />
                    <label className="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-base font-medium hover:bg-accent">
                        <ImagePlus className="size-5" aria-hidden />
                        {shownImage ? 'Andere foto kiezen' : 'Foto uploaden'}
                        <input
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            className="sr-only"
                            onChange={(e) => {
                                form.setData(
                                    'image',
                                    e.target.files?.[0] ?? null,
                                );
                                form.setData('remove_image', false);
                            }}
                        />
                    </label>
                    {(form.data.image || currentImage) && (
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11"
                            onClick={() => {
                                form.setData('image', null);
                                form.setData(
                                    'remove_image',
                                    editing && currentImage !== null,
                                );
                            }}
                        >
                            <X />
                            {form.data.image
                                ? 'Keuze ongedaan maken'
                                : 'Foto verwijderen'}
                        </Button>
                    )}
                    {form.errors.image && (
                        <p
                            role="alert"
                            className="text-sm font-bold text-primary"
                        >
                            {form.errors.image}
                        </p>
                    )}
                    <p className="text-sm text-muted-foreground">
                        Zonder foto krijgt het product een gestileerd pictogram
                        van zijn categorie.
                    </p>
                </aside>
            </form>
        </>
    );
}
