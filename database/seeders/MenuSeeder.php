<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Het menu uit docs/run/01-analist.md §5: 4 categorieën, 27 producten.
 * Idempotent: updateOrCreate op slug. Foto's koppelen op public/img/menu/<slug>.(webp|jpg).
 */
class MenuSeeder extends Seeder
{
    /**
     * @var array<string, list<array{string, int, string}>> categorie => [[naam, centen, omschrijving], …]
     */
    private const array MENU = [
        'Frieten' => [
            ['Kleine friet', 300, 'Krokant gebakken in ossenvet, net genoeg voor één'],
            ['Medium friet', 350, 'Onze klassieker: goudbruin, warm en goed gevuld'],
            ['Grote friet', 420, 'Voor de echte honger of om te delen'],
        ],
        'Snacks' => [
            ['Frikandel', 220, 'De klassieker, krokant vanbuiten, sappig vanbinnen'],
            ['Frikandel speciaal', 290, 'Met curryketchup, mayonaise en fijne ajuin'],
            ['Curryworst', 250, 'Pittig gekruide worst, lekker knapperig gebakken'],
            ['Bicky Burger', 400, 'Krokant broodje, Bicky-saus, ajuin en augurk'],
            ['Kaaskroket', 250, 'Romige kaas in een goudbruin krokant korstje'],
            ['Garnaalkroket', 350, 'Rijk gevuld met grijze Noordzeegarnalen'],
            ['Vleeskroket', 230, 'Zachte vulling van rundsvlees, krokant gepaneerd'],
            ['Boulet', 280, 'Stevige gehaktbal, huisgemaakt gekruid'],
            ['Kipcorn', 250, 'Krokante kipsnack met een vleugje maïs'],
            ['Mexicano', 320, 'Grof gehakt, pikant gekruid, met een bite'],
            ['Cervela', 250, 'Gerookte worst, krokant gebakken, licht gerookt'],
            ['Lucifer', 250, 'Lange pikante snack voor wie van vuur houdt'],
        ],
        'Sauzen' => [
            ['Mayonaise', 90, 'Echte Belgische frietmayonaise, vol en romig'],
            ['Ketchup', 90, 'Zoet en tomatig, voor jong en oud'],
            ['Andalouse', 100, 'Licht pikant met paprika en fijne ajuin'],
            ['Samurai', 100, 'Pittig met harissa, voor wie het heet wil'],
            ['Tartaar', 100, 'Fris met augurk, kappertjes en kruiden'],
            ['Stoofvleessaus', 280, 'Warme stoofvleessaus zoals bij grootmoeder'],
        ],
        'Dranken' => [
            ['Coca-Cola', 220, 'Ijskoud blikje 33 cl'],
            ['Coca-Cola Zero', 220, 'Zelfde smaak, zonder suiker, 33 cl'],
            ['Fanta Orange', 220, 'Bruisend sinaasappel, 33 cl'],
            ['Ice Tea', 230, 'Fris en licht zoet, 33 cl'],
            ['Plat water', 200, 'Gewoon goed, 50 cl'],
            ['Jupiler', 250, 'Belgische pils, ijskoud, 33 cl'],
        ],
    ];

    public function run(): void
    {
        $categoryOrder = 1;

        foreach (self::MENU as $categoryName => $products) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $categoryOrder++],
            );

            $productOrder = 1;

            foreach ($products as [$name, $priceCents, $description]) {
                $slug = Str::slug($name);

                $product = Product::query()->firstOrNew(['slug' => $slug]);
                $product->fill([
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => $description,
                    'price_cents' => $priceCents,
                    'sort_order' => $productOrder++,
                ]);

                // Zichtbaar/uitverkocht laten we staan als de baas ze al wijzigde.
                if (! $product->exists) {
                    $product->is_visible = true;
                    $product->is_sold_out = false;
                }

                // Foto uit public/img/menu/<slug>.(webp|jpg) koppelen; een upload via admin blijft staan.
                $menuImage = Product::menuImagePath($slug);

                if ($menuImage !== null && ($product->image_path === null || Product::isPublicAssetPath($product->image_path))) {
                    $product->image_path = $menuImage;
                }

                $product->save();
            }
        }
    }
}
