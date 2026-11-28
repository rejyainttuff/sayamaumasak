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
use Illuminate\Support\Facades\Hash;
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
        $this->get('/stories/filosofi-base-genep-dapur-bali')->assertStatus(200)->assertSee('Base Genep');
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

    public function test_password_reset_updates_password_directly_without_smtp_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'made@baliculinary.test',
            'password' => Hash::make('OldPassword123'),
        ]);

        $this->get('/change-password')
            ->assertStatus(200)
            ->assertSee('Simpan Password Baru')
            ->assertDontSee('Kirim Kode OTP');

        // Unregistered email fails validation
        $this->post('/change-password', [
            'email' => 'tidakada@baliculinary.test',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertSessionHasErrors('email');

        // Registered email updates password directly with secure hash
        $this->post('/change-password', [
            'email' => $user->email,
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewSecurePass123', $user->fresh()->password));
    }

    public function test_three_role_system_user_helper_admin_and_food_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $helper = User::factory()->create(['role' => 'helper']);
        $user = User::factory()->create(['role' => 'user', 'can_submit_recipes' => false]);

        // Role 'contributor' is NOT allowed in role management
        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'contributor'])
            ->assertSessionHasErrors('role');

        // Helper can access recipe approvals, but NOT admin user management or food management
        $this->actingAs($helper)->get('/admin/recipe-approvals')->assertStatus(200);
        $this->actingAs($helper)->get('/admin/users')->assertStatus(403);
        $this->actingAs($helper)->get('/foods')->assertStatus(403);

        // Admin can access Food Management (index, create, edit)
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);
        $this->actingAs($admin)->get('/foods')->assertStatus(200)->assertSee('Food Management');
        $this->actingAs($admin)->get('/foods/create')->assertStatus(200);
        $this->actingAs($admin)->post('/foods', [
            'name' => 'Serombotan Klungkung',
            'category_id' => $category->id,
            'origin_area' => 'Klungkung',
            'short_description' => 'Sayuran segar khas Klungkung dengan bumbu kalas.',
        ])->assertRedirect('/foods');

        $food = Food::where('name', 'Serombotan Klungkung')->firstOrFail();
        $this->actingAs($admin)->get("/foods/{$food->id}/edit")->assertStatus(200);

        // Logout restores guest
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_visitor_tracking_and_admin_dashboard_statistics_use_database_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $creator = User::factory()->create(['role' => 'user', 'can_submit_recipes' => true]);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        $food = Food::create([
            'category_id' => $category->id,
            'user_id' => $creator->id,
            'name' => 'Lawar Bali',
            'slug' => 'lawar-bali',
            'origin_area' => 'Gianyar',
            'approval_status' => 'approved',
        ]);

        Recipe::create([
            'food_id' => $food->id,
            'user_id' => $creator->id,
            'title' => 'Resep Lawar Bali',
            'difficulty' => 'sedang',
            'video_url' => 'https://example.com/video.mp4',
            'approval_status' => 'pending',
        ]);

        CulinaryStory::create([
            'food_id' => $food->id,
            'user_id' => $creator->id,
            'title' => 'Sejarah Lawar',
            'slug' => 'sejarah-lawar',
            'content' => 'Cerita kuliner Bali.',
            'approval_status' => 'approved',
        ]);

        Review::create([
            'user_id' => $creator->id,
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
    }

    public function test_contributor_application_grants_permission_while_keeping_user_role(): void
    {
        $user = User::factory()->create(['role' => 'user', 'can_submit_recipes' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post('/contributor/apply', ['reason' => 'Terlalu singkat.'])
            ->assertSessionHasErrors('reason');

        $longReason = str_repeat('Saya ingin melestarikan resep kuliner tradisional Bali dari keluarga di Buleleng. ', 4);
        $this->actingAs($user)
            ->post('/contributor/apply', [
                'specialty' => 'Masakan Bali Utara',
                'reason' => $longReason,
            ])
            ->assertRedirect('/my-submissions');

        $contributorRequest = ContributorRequest::firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/contributor-requests/{$contributorRequest->id}/status", ['status' => 'approved'])
            ->assertRedirect();

        $user->refresh();
        // IMPORTANT: Role stays 'user', only can_submit_recipes becomes true!
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->can_submit_recipes);
    }

    public function test_recipe_revision_rejection_reason_ownership_and_no_kirim_makanan_in_my_submissions(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'can_submit_recipes' => true]);
        $otherUser = User::factory()->create(['role' => 'user', 'can_submit_recipes' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        $food = Food::create([
            'category_id' => $category->id,
            'name' => 'Sate Plecing',
            'slug' => 'sate-plecing',
            'origin_area' => 'Singaraja',
            'approval_status' => 'approved',
        ]);

        $recipe = Recipe::create([
            'food_id' => $food->id,
            'user_id' => $owner->id,
            'title' => 'Sate Plecing Draft',
            'prep_time_minutes' => 15,
            'cook_time_minutes' => 25,
            'servings' => 4,
            'difficulty' => 'sedang',
            'approval_status' => 'draft',
        ]);

        // Other user CANNOT edit owner's recipe
        $this->actingAs($otherUser)->get("/recipes/{$recipe->id}/edit")->assertStatus(403);

        // Owner CAN edit draft without duplicating recipe
        $this->actingAs($owner)
            ->put("/recipes/{$recipe->id}", [
                'food_id' => $food->id,
                'title' => 'Sate Plecing Singaraja Revisi',
                'prep_time_minutes' => 20,
                'cook_time_minutes' => 30,
                'servings' => 4,
                'difficulty' => 'sedang',
                'submission_action' => 'resubmit',
            ])
            ->assertRedirect('/my-submissions');

        $this->assertSame(1, Recipe::count());
        $recipe->refresh();
        $this->assertSame('pending', $recipe->approval_status);
        $this->assertSame(1, $recipe->revision_count);

        // Admin rejects with reason; owner sees reason on /my-submissions and NO "Kirim Makanan" button
        $this->actingAs($admin)
            ->patch("/recipes/{$recipe->id}/reject", [
                'rejection_reason' => 'Mohon lengkapi takaran bumbu plecing.',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get('/my-submissions')
            ->assertStatus(200)
            ->assertSee('Mohon lengkapi takaran bumbu plecing.')
            ->assertDontSee('Kirim Makanan');
    }

    public function test_contributor_level_system_reading_progress_recipe_bonus_and_level_3_story_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $creator = User::factory()->create([
            'role' => 'user',
            'can_submit_recipes' => true,
            'contributor_level' => 1,
            'reading_seconds' => 0,
        ]);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        $food = Food::create([
            'category_id' => $category->id,
            'user_id' => $creator->id,
            'name' => 'Babi Guling',
            'slug' => 'babi-guling',
            'origin_area' => 'Gianyar',
            'approval_status' => 'approved',
        ]);

        // Level 1 cannot submit Culinary Story
        $this->actingAs($creator)->get('/stories/create')->assertStatus(403);

        // Reading heartbeat updates DB
        $this->actingAs($creator)
            ->postJson('/reading/heartbeat', [
                'content_type' => 'food',
                'content_id' => $food->id,
                'session_token' => 'sessiontoken12345',
                'delta_seconds' => 20,
            ])
            ->assertStatus(200)
            ->assertJsonPath('credited_seconds', 20);

        $levelService = app(ContributorLevelService::class);
        $this->assertSame(2, $levelService->calculateReadingProgress(600)['reading_level']);

        // Recipe approval grants +5 levels ONCE
        $recipe = Recipe::create([
            'food_id' => $food->id,
            'user_id' => $creator->id,
            'title' => 'Resep Babi Guling',
            'prep_time_minutes' => 30,
            'cook_time_minutes' => 120,
            'servings' => 6,
            'difficulty' => 'sulit',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($admin)->patch("/recipes/{$recipe->id}/approve")->assertRedirect();
        $creator->refresh();
        $this->assertSame(6, $creator->contributor_level);
        $this->assertSame(1, ContributorLevelLog::where('source', 'recipe_bonus')->where('recipe_id', $recipe->id)->count());

        // Now Level 6 >= 3 can submit Culinary Story
        $this->actingAs($creator)->get('/stories/create')->assertStatus(200);
    }

    public function test_culinary_story_submission_multiple_photos_and_admin_approval_workflow(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $creator = User::factory()->create([
            'role' => 'user',
            'can_submit_recipes' => true,
            'contributor_level' => 3,
        ]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        $response = $this->actingAs($creator)->post('/stories', [
            'title' => 'Kisah Warung Lawar Kuwir Ubud',
            'content' => str_repeat('Kisah turun-temurun meracik lawar kuwir dengan bumbu base genep di Ubud. ', 3),
            'thumbnail' => UploadedFile::fake()->createWithContent('thumb.png', $pngBytes),
            'gallery_images' => [
                UploadedFile::fake()->createWithContent('gal1.png', $pngBytes),
                UploadedFile::fake()->createWithContent('gal2.png', $pngBytes),
                UploadedFile::fake()->createWithContent('gal3.png', $pngBytes),
            ],
        ]);

        $response->assertRedirect(route('submissions.index'));

        $story = CulinaryStory::where('title', 'Kisah Warung Lawar Kuwir Ubud')->firstOrFail();
        $this->assertSame('pending', $story->approval_status);
        $this->assertCount(3, $story->gallery_images ?? []);

        // User sees Pending story in My Submissions
        $this->actingAs($creator)
            ->get('/my-submissions')
            ->assertStatus(200)
            ->assertSee('Kisah Warung Lawar Kuwir Ubud')
            ->assertSee('Pending');

        // Public stories page hides pending story
        $this->get('/stories')
            ->assertStatus(200)
            ->assertDontSee('Kisah Warung Lawar Kuwir Ubud');

        // Admin sees story in Story Submissions
        $this->actingAs($admin)
            ->get('/admin/stories?status=pending')
            ->assertStatus(200)
            ->assertSee('Kisah Warung Lawar Kuwir Ubud');

        // Admin rejects with reason -> user sees reason in My Submissions
        $this->actingAs($admin)
            ->patch("/admin/stories/{$story->id}/reject", [
                'rejection_reason' => 'Tambahkan sumber sejarah daerah Ubud.',
            ])
            ->assertRedirect();

        $story->refresh();
        $this->assertSame('rejected', $story->approval_status);
        $this->assertSame('Tambahkan sumber sejarah daerah Ubud.', $story->rejection_reason);

        $this->actingAs($creator)
            ->get('/my-submissions')
            ->assertStatus(200)
            ->assertSee('Tambahkan sumber sejarah daerah Ubud.');

        // Admin approves -> becomes public and clears rejection reason
        $this->actingAs($admin)
            ->patch("/admin/stories/{$story->id}/approve")
            ->assertRedirect();

        $story->refresh();
        $this->assertSame('approved', $story->approval_status);
        $this->assertNull($story->rejection_reason);

        $this->get('/stories')
            ->assertStatus(200)
            ->assertSee('Kisah Warung Lawar Kuwir Ubud');

        // Verify main thumbnail and ALL 3 gallery images render on the story detail page
        $detailResponse = $this->get('/stories/'.$story->slug)->assertStatus(200);
        $detailResponse->assertSee(ltrim((string) $story->thumbnail, '/'));
        foreach ($story->gallery_images ?? [] as $galleryPath) {
            $detailResponse->assertSee(ltrim((string) $galleryPath, '/'));
        }

        // Verify another story's photos never leak into this story
        $otherStory = CulinaryStory::create([
            'user_id' => $creator->id,
            'title' => 'Cerita Lain Tabanan',
            'slug' => 'cerita-lain-tabanan',
            'content' => str_repeat('Konten cerita lain dari Tabanan yang cukup panjang. ', 3),
            'thumbnail' => '/storage/stories/unique-tabanan-thumb.png',
            'gallery_images' => ['/storage/stories/gallery/unique-tabanan-gal.png'],
            'approval_status' => 'approved',
        ]);
        $detailResponse->assertDontSee('unique-tabanan-thumb.png');
        $detailResponse->assertDontSee('unique-tabanan-gal.png');
        $this->assertNotSame($story->id, $otherStory->id);
    }

    public function test_culinary_story_rejects_more_than_six_gallery_photos(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create([
            'role' => 'user',
            'can_submit_recipes' => true,
            'contributor_level' => 3,
        ]);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $tooManyPhotos = [];
        for ($i = 1; $i <= 7; $i++) {
            $tooManyPhotos[] = UploadedFile::fake()->createWithContent("photo{$i}.png", $pngBytes);
        }

        $this->actingAs($creator)
            ->post('/stories', [
                'title' => 'Cerita Dengan Tujuh Foto',
                'content' => str_repeat('Konten cerita kuliner Bali yang cukup panjang untuk validasi. ', 3),
                'gallery_images' => $tooManyPhotos,
            ])
            ->assertSessionHasErrors('gallery_images');
    }

    public function test_dynamic_beranda_photo_of_the_day_and_about_expandable_cards(): void
    {
        $creator = User::factory()->create(['role' => 'user', 'name' => 'Made Karya']);
        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);

        Food::create([
            'category_id' => $category->id,
            'user_id' => $creator->id,
            'name' => 'Sate Lilit Klungkung',
            'slug' => 'sate-lilit-klungkung',
            'origin_area' => 'Klungkung',
            'short_description' => 'Sate lilit ikan laut khas Klungkung.',
            'thumbnail' => '/Assets/images/baliculinarylogo.png',
            'approval_status' => 'approved',
        ]);

        Food::create([
            'category_id' => $category->id,
            'user_id' => $creator->id,
            'name' => 'Makanan Pending Rahasia',
            'slug' => 'makanan-pending-rahasia',
            'origin_area' => 'Badung',
            'thumbnail' => '/Assets/images/baliculinarylogo.png',
            'approval_status' => 'pending',
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('baliculinarylogo.png')
            ->assertSee('Photo of the Day')
            ->assertSee('Sate Lilit Klungkung')
            ->assertDontSee('Makanan Pending Rahasia')
            ->assertDontSee('class="home-dynamic-section" data-reveal', false);

        $this->get('/photo-of-the-day')
            ->assertStatus(200)
            ->assertSee('Sate Lilit Klungkung')
            ->assertSee('Made Karya')
            ->assertDontSee('Makanan Pending Rahasia');

        $this->get('/about')
            ->assertStatus(200)
            ->assertSee('data-expand-card')
            ->assertSee('data-expand-close')
            ->assertSee('about-card-full')
            ->assertSee('Cerita di Balik Bali Culinary')
            ->assertSee('Visi &amp; Misi', false)
            ->assertSee('Mari lestarikan kuliner Bali bersama Bali Culinary');
    }

    public function test_beranda_still_renders_cleanly_when_database_is_empty(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Dari Rasa')
            ->assertSee('Photo of the Day')
            ->assertSee('Mari lestarikan kuliner Bali bersama Bali Culinary');
    }

    public function test_mobile_dashboard_capsule_drawer_and_canonical_logo_across_roles(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $helper = User::factory()->create(['role' => 'helper']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/account')
            ->assertStatus(200)
            ->assertSee('mobile-dash-capsule-btn')
            ->assertSee('data-drawer-open')
            ->assertSee('baliculinarylogo.png');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSee('mobile-dash-capsule-btn')
            ->assertSee('data-drawer-open')
            ->assertSee('baliculinarylogo.png');

        $this->actingAs($helper)
            ->get('/admin/stories')
            ->assertStatus(200)
            ->assertSee('mobile-dash-capsule-btn')
            ->assertSee('data-drawer-open')
            ->assertSee('baliculinarylogo.png');
    }

    public function test_only_owner_or_admin_can_delete_recipe_and_story_submissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $helper = User::factory()->create(['role' => 'helper']);
        $owner = User::factory()->create([
            'role' => 'user',
            'can_submit_recipes' => true,
            'contributor_level' => 4,
        ]);
        $otherContributor = User::factory()->create([
            'role' => 'user',
            'can_submit_recipes' => true,
            'contributor_level' => 5,
        ]);

        $category = Category::create(['name' => 'Hidangan Utama', 'slug' => 'hidangan-utama']);
        $food = Food::create([
            'category_id' => $category->id,
            'name' => 'Bebek Betutu',
            'slug' => 'bebek-betutu',
            'origin_area' => 'Gianyar',
            'approval_status' => 'approved',
        ]);

        $recipeByOwner = Recipe::create([
            'food_id' => $food->id,
            'user_id' => $owner->id,
            'title' => 'Resep Bebek Betutu Milik Owner',
            'difficulty' => 'sedang',
            'approval_status' => 'pending',
        ]);

        $recipeForAdmin = Recipe::create([
            'food_id' => $food->id,
            'user_id' => $owner->id,
            'title' => 'Resep Untuk Dihapus Admin',
            'difficulty' => 'mudah',
            'approval_status' => 'approved',
        ]);

        $storyByOwner = CulinaryStory::create([
            'food_id' => $food->id,
            'user_id' => $owner->id,
            'title' => 'Cerita Bebek Betutu Owner',
            'slug' => 'cerita-bebek-betutu-owner',
            'content' => str_repeat('Kisah memasak bebek betutu di dapur tradisional Bali. ', 3),
            'approval_status' => 'pending',
        ]);

        $storyForAdmin = CulinaryStory::create([
            'food_id' => $food->id,
            'user_id' => $owner->id,
            'title' => 'Cerita Untuk Dihapus Admin',
            'slug' => 'cerita-untuk-dihapus-admin',
            'content' => str_repeat('Kisah kuliner Bali untuk pengujian hapus oleh admin. ', 3),
            'approval_status' => 'approved',
        ]);

        // Another contributor CANNOT delete owner's recipe or story
        $this->actingAs($otherContributor)->delete("/recipes/{$recipeByOwner->id}")->assertStatus(403);
        $this->actingAs($otherContributor)->delete("/stories/{$storyByOwner->id}")->assertStatus(403);

        // Helper CANNOT delete other user's recipe or story
        $this->actingAs($helper)->delete("/recipes/{$recipeByOwner->id}")->assertStatus(403);
        $this->actingAs($helper)->delete("/stories/{$storyByOwner->id}")->assertStatus(403);

        $this->assertDatabaseHas('recipes', ['id' => $recipeByOwner->id]);
        $this->assertDatabaseHas('culinary_stories', ['id' => $storyByOwner->id]);

        // Owner CAN delete their own recipe and story
        $this->actingAs($owner)->delete("/recipes/{$recipeByOwner->id}")->assertRedirect();
        $this->actingAs($owner)->delete("/stories/{$storyByOwner->id}")->assertRedirect();
        $this->assertDatabaseMissing('recipes', ['id' => $recipeByOwner->id]);
        $this->assertDatabaseMissing('culinary_stories', ['id' => $storyByOwner->id]);

        // Admin CAN delete any user's recipe and story
        $this->actingAs($admin)->delete("/recipes/{$recipeForAdmin->id}")->assertRedirect();
        $this->actingAs($admin)->delete("/stories/{$storyForAdmin->id}")->assertRedirect();
        $this->assertDatabaseMissing('recipes', ['id' => $recipeForAdmin->id]);
        $this->assertDatabaseMissing('culinary_stories', ['id' => $storyForAdmin->id]);
    }
}
