<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContributorLevelLog;
use App\Models\ContributorRequest;
use App\Models\CulinaryStory;
use App\Models\Food;
use App\Models\Recipe;
use App\Models\Review;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\ContributorLevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BaliCulinaryFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_public_pages_without_auto_login(): void
    {
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);
        Food::create([
            'category_id' => $category->id,
            'name' => 'Ayam Betutu',
            'slug' => 'ayam-betutu',
            'origin_area' => 'Jembrana',
            'short_description' => 'Ayam berbumbu khas Bali.',
            'approval_status' => 'approved',
        ]);

        $this->get('/')->assertStatus(200);
        $this->assertGuest();

        $this->get('/explore')->assertStatus(200);
        $this->get('/stories')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/food-detail/ayam-betutu')->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_for_protected_features(): void
    {
        $this->get('/favorites')->assertRedirect('/login');
        $this->get('/account')->assertRedirect('/login');
        $this->get('/recipe/create')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');

        $this->post('/favorites/toggle/ayam-betutu')->assertRedirect('/login');
        $this->post('/foods/ayam-betutu/reviews', ['rating' => 5])->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_dashboard_and_logout_restores_guest(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_visitor_tracking_and_admin_dashboard_statistics_use_database_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = User::factory()->create(['role' => 'contributor']);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        $food = Food::create([
            'category_id' => $category->id,
            'user_id' => $contributor->id,
            'name' => 'Lawar Bali',
            'slug' => 'lawar-bali',
            'origin_area' => 'Gianyar',
            'approval_status' => 'approved',
        ]);

        Recipe::create([
            'food_id' => $food->id,
            'user_id' => $contributor->id,
            'title' => 'Resep Lawar Bali',
            'difficulty' => 'sedang',
            'video_url' => 'https://example.com/video.mp4',
            'approval_status' => 'pending',
        ]);

        CulinaryStory::create([
            'food_id' => $food->id,
            'user_id' => $contributor->id,
            'title' => 'Sejarah Lawar',
            'slug' => 'sejarah-lawar',
            'content' => 'Cerita kuliner Bali.',
            'approval_status' => 'approved',
        ]);

        Review::create([
            'user_id' => $contributor->id,
            'food_id' => $food->id,
            'rating' => 5,
            'comment' => 'Lezat!',
        ]);

        $this->get('/')->assertStatus(200);
        $this->get('/explore')->assertStatus(200);
        $this->assertSame(2, SiteVisit::count());

        $response = $this->actingAs($admin)->get('/admin?range=30d');
        $response->assertStatus(200);
        $response->assertSee('Total Makanan');
        $response->assertSee('Total Resep');
        $response->assertSee('Total Kontributor');
        $response->assertSee('Total Ulasan');
        $response->assertViewHas('contentDistribution', [
            'labels' => ['Resep', 'Makanan', 'Cerita', 'Video'],
            'data' => [1, 1, 1, 1],
        ]);
        $response->assertViewHas('contentSeries', fn (array $series): bool => $series['range'] === '30d' && count($series['labels']) === 30);
        $this->assertSame(2, SiteVisit::count());
    }

    public function test_new_account_has_empty_favorites_reviews_and_recently_viewed_and_can_edit_profile(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user', 'bio' => null, 'avatar' => null]);

        $accountResponse = $this->actingAs($user)->get('/account');
        $accountResponse->assertStatus(200);
        $accountResponse->assertHeader('Pragma', 'no-cache');
        $accountResponse->assertSee('Cari Apapun');
        $accountResponse->assertViewHas('favoriteSamples', []);
        $accountResponse->assertViewHas('recentSamples', []);
        $accountResponse->assertViewHas('reviews', []);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $avatar = UploadedFile::fake()->createWithContent('avatar.png', $pngBytes);
        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => 'Made Sari',
                'bio' => 'Pecinta kuliner tradisional Bali.',
                'avatar' => $avatar,
            ])
            ->assertRedirect('/account');

        $user->refresh();
        $this->assertSame('Made Sari', $user->name);
        $this->assertSame('Pecinta kuliner tradisional Bali.', $user->bio);
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('/storage/avatars/', $user->avatar);

        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);
        Food::create([
            'category_id' => $category->id,
            'name' => 'Sate Lilit',
            'slug' => 'sate-lilit',
            'origin_area' => 'Klungkung',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user)->get('/food-detail/sate-lilit')->assertStatus(200);
        $this->actingAs($user)
            ->get('/account')
            ->assertViewHas('recentSamples', fn (array $items): bool => count($items) === 1 && $items[0]['slug'] === 'sate-lilit');
    }

    public function test_contributor_application_enforces_200_chars_and_admin_pages_work(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Too short (< 200 chars) should fail validation
        $this->actingAs($user)
            ->post('/contributor/apply', [
                'specialty' => 'Masakan Bali Utara',
                'reason' => 'Terlalu singkat.',
            ])
            ->assertSessionHasErrors('reason');

        // Valid >= 200 chars should succeed
        $longReason = str_repeat('Saya ingin melestarikan resep kuliner tradisional Bali dari keluarga di Buleleng. ', 4);
        $this->actingAs($user)
            ->post('/contributor/apply', [
                'specialty' => 'Masakan Bali Utara',
                'reason' => $longReason,
            ])
            ->assertRedirect('/my-submissions');

        $contributorRequest = ContributorRequest::first();
        $this->assertNotNull($contributorRequest);
        $this->assertSame('pending', $contributorRequest->status);

        // Admin views dedicated contributors & users & recipe-approvals pages
        $this->actingAs($admin)->get('/admin/contributors')->assertStatus(200)->assertSee($user->email);
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee($user->name);
        $this->actingAs($admin)->get('/admin/recipe-approvals')->assertStatus(200);

        // Approve contributor application -> upgrades role to contributor
        $this->actingAs($admin)
            ->post("/admin/contributor-requests/{$contributorRequest->id}/status", ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('contributor', $user->fresh()->role);
    }

    public function test_contributor_level_system_reading_progress_recipe_bonus_and_level_3_story_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contributor = User::factory()->create(['role' => 'contributor', 'contributor_level' => 1, 'reading_seconds' => 0]);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        $food = Food::create([
            'category_id' => $category->id,
            'user_id' => $contributor->id,
            'name' => 'Babi Guling',
            'slug' => 'babi-guling',
            'origin_area' => 'Gianyar',
            'approval_status' => 'approved',
        ]);

        // Contributor Level 1 CANNOT access or submit Culinary Story (requires Level 3)
        $this->actingAs($contributor)->get('/stories/create')->assertStatus(403);
        $this->actingAs($contributor)->post('/stories', [
            'title' => 'Kisah Kuliner',
            'content' => str_repeat('Isi cerita kuliner Bali tradisional. ', 5),
        ])->assertStatus(403);

        // Active reading heartbeat updates reading_seconds in DB
        $this->actingAs($contributor)
            ->postJson('/reading/heartbeat', [
                'content_type' => 'food',
                'content_id' => $food->id,
                'session_token' => 'sessiontoken12345',
                'delta_seconds' => 20,
            ])
            ->assertStatus(200)
            ->assertJsonPath('credited_seconds', 20)
            ->assertJsonPath('total_reading_seconds', 20);

        // Verify progressive reading level calculation: 600s -> Level 2, 1500s -> Level 3
        $levelService = app(ContributorLevelService::class);
        $this->assertSame(1, $levelService->calculateReadingProgress(599)['reading_level']);
        $this->assertSame(2, $levelService->calculateReadingProgress(600)['reading_level']);
        $this->assertSame(3, $levelService->calculateReadingProgress(1500)['reading_level']);

        // Pending recipe grants NO bonus; Admin approval grants +5 levels ONCE
        $recipe = Recipe::create([
            'food_id' => $food->id,
            'user_id' => $contributor->id,
            'title' => 'Resep Babi Guling',
            'prep_time_minutes' => 30,
            'cook_time_minutes' => 120,
            'servings' => 6,
            'difficulty' => 'sulit',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/recipes/{$recipe->id}/approve")->assertRedirect();
        $contributor->refresh();
        $this->assertSame(6, $contributor->contributor_level);
        $this->assertSame(1, $contributor->approved_recipes_count);
        $this->assertSame(1, ContributorLevelLog::where('source', 'recipe_bonus')->where('recipe_id', $recipe->id)->count());

        // Re-approving same recipe does NOT grant duplicate bonus
        $this->actingAs($admin)->patch("/recipes/{$recipe->id}/reject")->assertRedirect();
        $this->actingAs($admin)->patch("/recipes/{$recipe->id}/approve")->assertRedirect();
        $contributor->refresh();
        $this->assertSame(6, $contributor->contributor_level);
        $this->assertSame(1, ContributorLevelLog::where('source', 'recipe_bonus')->where('recipe_id', $recipe->id)->count());

        // Now Contributor Level 6 (>= 3) CAN access /stories/create and submit a story!
        $this->actingAs($contributor)->get('/stories/create')->assertStatus(200);
        $this->actingAs($contributor)->post('/stories', [
            'food_id' => $food->id,
            'title' => 'Tradisi Memasak Bali',
            'content' => str_repeat('Kisah lengkap tentang tradisi memasak Bali turun-temurun. ', 4),
        ])->assertRedirect('/stories');

        $story = CulinaryStory::where('title', 'Tradisi Memasak Bali')->first();
        $this->assertNotNull($story);

        // Dedicated Recipe Detail & Story Detail pages render cleanly
        $this->get("/recipes/{$recipe->id}")->assertStatus(200)->assertSee('Resep Babi Guling');
        $this->actingAs($contributor)->get("/stories/{$story->slug}")->assertStatus(200)->assertSee('Tradisi Memasak Bali');
    }
}
