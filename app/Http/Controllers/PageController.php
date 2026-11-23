<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home(): View
    {
        $foods = $this->getAllCulinaryData();
        $popularFoods = array_values(array_filter($foods, fn (array $food): bool => $food['is_popular']));

        return view('home', compact('foods', 'popularFoods'));
    }

    public function explore(Request $request): View
    {
        $allFoods = collect($this->getAllCulinaryData());
        $search = trim((string) $request->query('search', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $selectedRegion = trim((string) $request->query('region', ''));
        $selectedDifficulty = trim((string) $request->query('difficulty', ''));

        $categories = ['Makanan', 'Minuman', 'Jajan'];
        $regions = $allFoods->pluck('region')->unique()->sort()->values()->all();
        $difficulties = ['Mudah', 'Sedang', 'Sulit'];

        $filtered = $allFoods
            ->when($search !== '', function ($collection) use ($search) {
                $needle = mb_strtolower($search);

                return $collection->filter(function (array $food) use ($needle): bool {
                    return str_contains(mb_strtolower($food['name']), $needle)
                        || str_contains(mb_strtolower($food['description']), $needle)
                        || str_contains(mb_strtolower($food['region']), $needle)
                        || str_contains(mb_strtolower($food['category']), $needle);
                });
            })
            ->when($selectedCategory !== '', fn ($collection) => $collection->where('category', $selectedCategory))
            ->when($selectedRegion !== '', fn ($collection) => $collection->where('region', $selectedRegion))
            ->when($selectedDifficulty !== '', fn ($collection) => $collection->where('difficulty', $selectedDifficulty))
            ->values();

        $perPage = 8;
        $totalPages = max(1, (int) ceil($filtered->count() / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $foods = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

        $popularFoods = $allFoods->where('is_popular', true)->take(3)->values()->all();

        return view('explore', compact(
            'foods',
            'popularFoods',
            'categories',
            'regions',
            'difficulties',
            'search',
            'selectedCategory',
            'selectedRegion',
            'selectedDifficulty',
            'currentPage',
            'totalPages'
        ));
    }

    public function foodDetail(?string $slug = null): View
    {
        $foods = $this->getAllCulinaryData();
        $food = collect($foods)->firstWhere('slug', $slug) ?? $foods[0];
        $relatedFoods = collect($foods)
            ->reject(fn (array $item): bool => $item['slug'] === $food['slug'])
            ->take(3)
            ->values()
            ->all();

        return view('food-detail', compact('food', 'foods', 'relatedFoods'));
    }

    public function recipe(Request $request): View
    {
        $allFoods = collect($this->getAllCulinaryData());
        $selectedCategory = trim((string) $request->query('category', ''));
        $categories = ['Makanan', 'Minuman', 'Jajan'];

        $filtered = $allFoods
            ->when($selectedCategory !== '', fn ($collection) => $collection->where('category', $selectedCategory))
            ->values();

        $perPage = 9;
        $totalPages = max(1, (int) ceil($filtered->count() / $perPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $totalPages);
        $foods = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

        return view('recipe', compact('foods', 'categories', 'selectedCategory', 'currentPage', 'totalPages'));
    }

    public function createRecipe(): View
    {
        $categories = ['Makanan', 'Minuman', 'Jajan'];
        $difficulties = ['Mudah', 'Sedang', 'Sulit'];
        $regions = ['Badung', 'Bangli', 'Buleleng', 'Denpasar', 'Gianyar', 'Jembrana', 'Karangasem', 'Klungkung', 'Tabanan'];

        return view('recipe-create', compact('categories', 'difficulties', 'regions'));
    }

    public function storeRecipe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:80'],
            'difficulty' => ['required', 'in:Mudah,Sedang,Sulit'],
            'category' => ['required', 'in:Makanan,Minuman,Jajan'],
            'cook_time' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:1000'],
            'ingredients' => ['required', 'string', 'max:2000'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['nullable', 'string', 'max:500'],
        ]);

        $steps = array_values(array_filter(
            array_map(fn (?string $step): string => trim((string) $step), $validated['steps']),
            fn (string $step): bool => $step !== ''
        ));

        if ($steps === []) {
            return back()
                ->withInput()
                ->withErrors(['steps' => 'Minimal isi satu langkah memasak.']);
        }

        $ingredients = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $validated['ingredients']) ?: []),
            fn (string $line): bool => $line !== ''
        ));

        $slug = Str::slug($validated['name']).'-'.Str::lower(Str::random(4));

        $newRecipe = [
            'id' => time(),
            'slug' => $slug,
            'name' => $validated['name'],
            'region' => $validated['region'],
            'category' => $validated['category'],
            'difficulty' => $validated['difficulty'],
            'cook_time' => $validated['cook_time'],
            'rating' => 5.0,
            'reviews_count' => 1,
            'is_popular' => false,
            'description' => $validated['description'],
            'history' => $validated['description'],
            'ingredients' => $ingredients,
            'steps' => $steps,
            'notes' => 'Resep ini dibagikan oleh komunitas pecinta kuliner Bali di SayaMauMasak.',
            'image' => 'Assets/images/custom-recipe.jpg',
        ];

        $customRecipes = $request->session()->get('custom_recipes', []);
        array_unshift($customRecipes, $newRecipe);
        $request->session()->put('custom_recipes', $customRecipes);

        return redirect()
            ->route('food.detail', $slug)
            ->with('success', 'Resep "'.$validated['name'].'" berhasil dibagikan!');
    }

    public function favorites(): View
    {
        $foods = $this->getAllCulinaryData();

        return view('favorites', compact('foods'));
    }

    public function stories(): View
    {
        $stories = [
            [
                'title' => 'Filosofi Base Genep dalam Dapur Tradisional Bali',
                'region' => 'Denpasar',
                'read_time' => '4 menit baca',
                'excerpt' => 'Base genep bukan sekadar racikan bumbu, melainkan harmoni rasa yang diwariskan turun-temurun untuk upacara adat hingga santapan keluarga.',
            ],
            [
                'title' => 'Tradisi Megibung di Karangasem: Makan Bersama Tanpa Sekat',
                'region' => 'Karangasem',
                'read_time' => '5 menit baca',
                'excerpt' => 'Duduk melingkar menyantap nasi dan lauk khas Bali dalam satu dulang mengajarkan kebersamaan dan rasa syukur masyarakat Bali.',
            ],
            [
                'title' => 'Rahasia Aroma Asap Ayam Betutu Gilimanuk',
                'region' => 'Jembrana',
                'read_time' => '3 menit baca',
                'excerpt' => 'Proses memasak perlahan dalam balutan daun pisang dan bara sekam menghasilkan daging ayam empuk beraroma rempah yang memikat.',
            ],
            [
                'title' => 'Jaje Laklak: Manisnya Jajanan Pasar Pagi di Bali',
                'region' => 'Klungkung',
                'read_time' => '3 menit baca',
                'excerpt' => 'Dipanggang di atas cetakan tanah liat dengan aroma daun pandan, jaje laklak selalu menemani secangkir kopi Bali di pagi hari.',
            ],
        ];

        return view('stories', compact('stories'));
    }

    public function about(): View
    {
        return view('about');
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     region: string,
     *     category: string,
     *     difficulty: string,
     *     cook_time: string,
     *     rating: float,
     *     reviews_count: int,
     *     is_popular: bool,
     *     description: string,
     *     history: string,
     *     ingredients: array<int, string>,
     *     steps: array<int, string>,
     *     notes: string,
     *     image: string
     * }>
     */
    private function getAllCulinaryData(): array
    {
        $custom = session()->get('custom_recipes', []);

        return array_merge(is_array($custom) ? $custom : [], $this->getCulinaryData());
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     slug: string,
     *     name: string,
     *     region: string,
     *     category: string,
     *     difficulty: string,
     *     cook_time: string,
     *     rating: float,
     *     reviews_count: int,
     *     is_popular: bool,
     *     description: string,
     *     history: string,
     *     ingredients: array<int, string>,
     *     steps: array<int, string>,
     *     notes: string,
     *     image: string
     * }>
     */
    private function getCulinaryData(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'lawar-bali',
                'name' => 'Lawar Bali',
                'region' => 'Badung',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '45 menit',
                'rating' => 4.8,
                'reviews_count' => 124,
                'is_popular' => true,
                'description' => 'Campuran sayuran nangka muda atau kacang panjang, kelapa parut sangrai, dan daging cincang berbumbu rempah base genep.',
                'history' => 'Lawar merupakan sajian tradisional Bali yang selalu hadir dalam upacara adat maupun santapan sehari-hari. Istilah lawar berasal dari kata nglawar yang berarti mencincang halus bahan dan mencampurnya dengan bumbu khas Bali hingga menyatu sempurna.',
                'ingredients' => [
                    '250 gram kacang panjang, potong kecil-kecil',
                    '200 gram daging ayam/bebek cincang halus',
                    '150 gram kelapa parut setengah tua, bakar sebentar',
                    '3 sdm bumbu base genep Bali',
                    '4 siung bawang merah goreng & 3 siung bawang putih goreng',
                    '2 buah jeruk limau, ambil airnya',
                    'Garam, terasi bakar, dan cabai rawit secukupnya',
                ],
                'steps' => [
                    'Rebus kacang panjang sebentar hingga matang tetapi tetap renyah, lalu tiriskan.',
                    'Tumis bumbu base genep hingga harum, masukkan daging cincang dan masak sampai matang sempurna.',
                    'Campurkan kelapa parut, kacang panjang, daging berbumbu, serta bawang goreng ke dalam wadah besar.',
                    'Tambahkan garam, terasi bakar, irisan cabai rawit, dan perasan jeruk limau, lalu aduk merata hingga bumbu meresap.',
                    'Sajikan Lawar Bali bersama nasi hangat, sate lilit, dan sambal matah.',
                ],
                'notes' => 'Gunakan kelapa parut yang tidak terlalu tua agar tekstur lawar tetap lembut dan gurih. Sajikan segera setelah dicampur agar kesegarannya terjaga.',
                'image' => 'Assets/images/lawar.jpg',
            ],
            [
                'id' => 2,
                'slug' => 'ayam-betutu-gilimanuk',
                'name' => 'Ayam Betutu Gilimanuk',
                'region' => 'Jembrana',
                'category' => 'Makanan',
                'difficulty' => 'Sulit',
                'cook_time' => '120 menit',
                'rating' => 4.9,
                'reviews_count' => 210,
                'is_popular' => true,
                'description' => 'Ayam utuh yang dibumbui base genep khas Bali dan dimasak perlahan hingga empuk serta kaya aroma rempah.',
                'history' => 'Berasal dari kawasan Gilimanuk, Jembrana, Ayam Betutu dahulu disajikan khusus pada perayaan keagamaan dan upacara adat Bali sebelum dikenal luas sebagai ikon kuliner Pulau Dewata.',
                'ingredients' => [
                    '1 ekor ayam kampung utuh, bersihkan',
                    '5 sdm bumbu base genep cincang kasar',
                    '2 batang serai, memarkan',
                    '4 lembar daun salam & 5 lembar daun jeruk',
                    'Daun singkong rebus secukupnya untuk isian',
                    '500 ml air kelapa atau kaldu ayam',
                    'Daun pisang untuk membungkus',
                ],
                'steps' => [
                    'Lumuri seluruh permukaan dan bagian dalam ayam kampung dengan bumbu base genep yang telah ditumis harum.',
                    'Masukkan daun singkong berbumbu ke dalam rongga perut ayam, lalu diamkan selama 30 menit.',
                    'Ungkep ayam dengan air kelapa, serai, daun salam, dan daun jeruk menggunakan api kecil selama 60–90 menit hingga empuk.',
                    'Bungkus ayam dengan daun pisang lalu panggang sebentar di atas bara atau oven agar aroma smokey keluar.',
                    'Sajikan hangat bersama plecing kangkung, kacang tanah goreng, dan sambal matah.',
                ],
                'notes' => 'Memasak dengan api kecil secara perlahan adalah kunci utama agar bumbu meresap hingga ke tulang tanpa membuat daging hancur.',
                'image' => 'Assets/images/ayam-betutu.jpg',
            ],
            [
                'id' => 3,
                'slug' => 'sate-lilit-ikan',
                'name' => 'Sate Lilit Ikan',
                'region' => 'Gianyar',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '40 menit',
                'rating' => 4.9,
                'reviews_count' => 176,
                'is_popular' => true,
                'description' => 'Olahan daging ikan laut cincang yang dipadukan dengan kelapa parut dan bumbu Bali, dililitkan pada batang serai.',
                'history' => 'Sate Lilit menjadi simbol persatuan dalam budaya kuliner Bali. Tidak ditusuk seperti sate pada umumnya, adonan ikan berbumbu dililitkan erat pada batang serai atau bilah bambu pipih.',
                'ingredients' => [
                    '400 gram fillet ikan tenggiri atau tuna cincang halus',
                    '100 gram kelapa parut muda',
                    '3 sdm santan kental',
                    '3 sdm bumbu halus Bali (kunyit, kencur, lengkuas, bawang merah, bawang putih)',
                    '3 lembar daun jeruk, iris sangat halus',
                    '15 batang serai segar untuk tusukan',
                    'Garam dan gula merah sisir secukupnya',
                ],
                'steps' => [
                    'Campurkan ikan cincang, kelapa parut, santan kental, bumbu halus, irisan daun jeruk, garam, dan gula merah.',
                    'Uleni adonan hingga kalis dan mudah dibentuk.',
                    'Ambil segenggam adonan, lalu lilitkan memanjang pada ujung batang serai sambil dipadatkan.',
                    'Bakar sate di atas bara api sambil dibolak-balik hingga matang keemasan dan harum.',
                    'Angkat dan sajikan hangat dengan sambal matah.',
                ],
                'notes' => 'Basahi tangan dengan sedikit minyak kelapa saat melilit adonan agar tidak lengket dan hasilnya rapi.',
                'image' => 'Assets/images/sate-lilit.jpg',
            ],
            [
                'id' => 4,
                'slug' => 'nasi-campur-bali',
                'name' => 'Nasi Campur Bali',
                'region' => 'Denpasar',
                'category' => 'Makanan',
                'difficulty' => 'Sedang',
                'cook_time' => '60 menit',
                'rating' => 4.8,
                'reviews_count' => 152,
                'is_popular' => true,
                'description' => 'Sepiring nasi hangat lengkap dengan lawar, sate lilit, ayam sisit pedas, kacang tanah goreng, dan sambal matah.',
                'history' => 'Nasi Campur Bali mencerminkan kekayaan lauk dalam satu piring. Di Denpasar hingga sudut desa, hidangan ini menjadi menu favorit sarapan hingga makan siang masyarakat Bali.',
                'ingredients' => [
                    '2 porsi nasi putih hangat',
                    '100 gram ayam sisit bumbu Bali',
                    '2 tusuk sate lilit ikan',
                    '3 sdm lawar kacang panjang',
                    '1 butir telur pindang, belah dua',
                    '2 sdm sambal matah & kacang tanah goreng',
                ],
                'steps' => [
                    'Siapkan seluruh komponen lauk: ayam sisit berbumbu, lawar Bali, sate lilit bakar, dan telur pindang.',
                    'Tata nasi putih hangat di tengah piring saji beralaskan daun pisang.',
                    'Susun ayam sisit, lawar, sate lilit, dan telur mengelilingi nasi.',
                    'Tambahkan kacang tanah goreng dan sambal matah segar di atasnya.',
                ],
                'notes' => 'Alas daun pisang yang dipanaskan sebentar membuat aroma nasi campur terasa jauh lebih autentik.',
                'image' => 'Assets/images/nasi-campur.jpg',
            ],
            [
                'id' => 5,
                'slug' => 'bebek-betutu-ubud',
                'name' => 'Bebek Betutu Ubud',
                'region' => 'Gianyar',
                'category' => 'Makanan',
                'difficulty' => 'Sulit',
                'cook_time' => '150 menit',
                'rating' => 4.7,
                'reviews_count' => 89,
                'is_popular' => false,
                'description' => 'Bebek utuh yang dimarinasi rempah lengkap Bali dan dikukus serta dipanggang hingga lembut dan gurih.',
                'history' => 'Di kawasan Ubud, Gianyar, Bebek Betutu dikenal sebagai hidangan istimewa untuk menyambut tamu kehormatan dengan cita rasa rempah yang dalam.',
                'ingredients' => [
                    '1 ekor bebek muda utuh, bersihkan',
                    '6 sdm bumbu base genep lengkap',
                    '2 sdm air jeruk nipis',
                    '3 batang serai & 6 lembar daun jeruk',
                    'Daun pisang untuk membungkus rapat',
                ],
                'steps' => [
                    'Lumuri bebek dengan air jeruk nipis dan garam, diamkan 20 menit lalu bilas bersih.',
                    'Balurkan bumbu base genep secara merata hingga ke bagian dalam bebek.',
                    'Bungkus rapat dengan beberapa lapis daun pisang, lalu kukus selama 2 jam hingga empuk.',
                    'Panggang sebentar agar permukaan sedikit kering dan harum sebelum disajikan.',
                ],
                'notes' => 'Pilih bebek muda agar teksturnya cepat empuk dan tidak berbau amis.',
                'image' => 'Assets/images/bebek-betutu.jpg',
            ],
            [
                'id' => 6,
                'slug' => 'tum-ayam-bali',
                'name' => 'Tum Ayam Bali',
                'region' => 'Tabanan',
                'category' => 'Makanan',
                'difficulty' => 'Mudah',
                'cook_time' => '35 menit',
                'rating' => 4.6,
                'reviews_count' => 74,
                'is_popular' => false,
                'description' => 'Pepes ayam cincang khas Bali yang dibungkus daun pisang bersama santan dan rempah harum lalu dikukus.',
                'history' => 'Tum merupakan teknik memasak tradisional Bali dengan cara membungkus adonan daging berbumbu dalam daun pisang menyerupai bungkusan kecil dan mengukusnya.',
                'ingredients' => [
                    '300 gram daging ayam cincang',
                    '50 ml santan kental',
                    '1 butir telur ayam',
                    '3 sdm bumbu halus Bali',
                    'Daun salam, cabai rawit utuh, dan daun pisang',
                ],
                'steps' => [
                    'Aduk rata ayam cincang, santan, telur, dan bumbu halus Bali hingga tercampur sempurna.',
                    'Ambil 2 sendok makan adonan, letakkan di atas daun pisang bersama daun salam dan cabai rawit.',
                    'Bungkus rapi berbentuk tum lalu semat dengan lidi.',
                    'Kukus selama 25–30 menit hingga matang dan wangi.',
                ],
                'notes' => 'Tambahkan sedikit irisan daun kemangi bila suka aroma yang lebih segar.',
                'image' => 'Assets/images/tum-ayam.jpg',
            ],
            [
                'id' => 7,
                'slug' => 'es-daluman',
                'name' => 'Es Daluman Bali',
                'region' => 'Denpasar',
                'category' => 'Minuman',
                'difficulty' => 'Mudah',
                'cook_time' => '20 menit',
                'rating' => 4.8,
                'reviews_count' => 98,
                'is_popular' => false,
                'description' => 'Minuman segar cincau hijau khas Bali dengan sirup gula aren wangi pandan dan santan gurih.',
                'history' => 'Es Daluman terbuat dari perasan daun daluman (cincau hijau rambat) yang menyejukkan tubuh di tengah teriknya cuaca Bali.',
                'ingredients' => [
                    '250 gram daluman (cincau hijau segar)',
                    '200 ml santan rebus dengan sejumput garam',
                    '150 ml sirup gula aren & daun pandan',
                    'Es batu secukupnya',
                ],
                'steps' => [
                    'Rebus gula aren dengan air dan daun pandan hingga larut dan agak mengental, lalu dinginkan.',
                    'Rebus santan dengan sedikit garam sambil diaduk agar tidak pecah, dinginkan.',
                    'Sendokkan daluman segar ke dalam gelas saji.',
                    'Tuang sirup gula aren, santan, dan tambahkan es batu secukupnya.',
                ],
                'notes' => 'Sajikan dingin segera agar tekstur daluman tetap lembut dan segar.',
                'image' => 'Assets/images/es-daluman.jpg',
            ],
            [
                'id' => 8,
                'slug' => 'loloh-cemcem',
                'name' => 'Loloh Cemcem Penglipuran',
                'region' => 'Bangli',
                'category' => 'Minuman',
                'difficulty' => 'Mudah',
                'cook_time' => '25 menit',
                'rating' => 4.7,
                'reviews_count' => 63,
                'is_popular' => false,
                'description' => 'Minuman herbal tradisional khas Desa Penglipuran dari daun cemcem dengan perpaduan rasa asam, manis, asin, dan sedikit pedas.',
                'history' => 'Loloh Cemcem adalah minuman jamu tradisional kebanggaan Desa Penglipuran, Bangli, yang dipercaya membantu melancarkan pencernaan dan menyegarkan stamina.',
                'ingredients' => [
                    '2 genggam daun cemcem (kedondong hutan) muda',
                    '500 ml air kelapa muda atau air matang',
                    '2 sdm air asam jawa',
                    '2 sdm gula aren cair & sedikit garam',
                    '1 buah cabai rawit (opsional)',
                ],
                'steps' => [
                    'Cuci bersih daun cemcem lalu remas-remas atau blender bersama sebagian air hingga sarinya keluar.',
                    'Saring air perasan daun cemcem ke dalam wadah bersih.',
                    'Campurkan air asam jawa, gula aren cair, garam, dan air kelapa muda, aduk rata.',
                    'Simpan di kulkas hingga dingin lalu sajikan.',
                ],
                'notes' => 'Gunakan daun cemcem muda agar rasanya tidak terlalu sepat.',
                'image' => 'Assets/images/loloh-cemcem.jpg',
            ],
            [
                'id' => 9,
                'slug' => 'jaje-laklak',
                'name' => 'Jaje Laklak Bali',
                'region' => 'Klungkung',
                'category' => 'Jajan',
                'difficulty' => 'Mudah',
                'cook_time' => '30 menit',
                'rating' => 4.9,
                'reviews_count' => 141,
                'is_popular' => false,
                'description' => 'Kue tradisional tepung beras beraroma pandan yang dipanggang di cetakan tanah liat, disajikan dengan kelapa parut dan saus gula merah.',
                'history' => 'Jaje Laklak adalah jajanan pasar tradisional khas Bali yang identik dengan warna hijau alami dari daun suji dan pandan serta taburan kelapa parut gurih.',
                'ingredients' => [
                    '200 gram tepung beras',
                    '400 ml santan cair hangat',
                    '50 ml air perasan daun pandan dan daun suji',
                    '1/2 sdt garam',
                    '100 gram kelapa parut kukus & sirup gula merah kental',
                ],
                'steps' => [
                    'Campurkan tepung beras, garam, santan hangat, dan air pandan suji, aduk hingga adonan licin.',
                    'Panaskan cetakan kue lumpur atau tanah liat dengan api kecil dan olesi sedikit minyak.',
                    'Tuang adonan hingga 3/4 cetakan, tutup, dan panggang sampai berpori serta matang.',
                    'Angkat Jaje Laklak, taburi kelapa parut kukus, dan siram dengan saus gula merah kental.',
                ],
                'notes' => 'Gunakan api kecil agar bagian bawah jaje laklak matang harum tanpa cepat gosong.',
                'image' => 'Assets/images/jaje-laklak.jpg',
            ],
        ];
    }
}
