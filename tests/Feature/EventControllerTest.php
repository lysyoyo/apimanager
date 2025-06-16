<?php

// tests/Feature/EventControllerTest.php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Category;
use Carbon\Carbon;

class EventControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_can_get_paginated_events()
    {
        Event::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'description', 'event_date',
                        'location', 'max_participants', 'category', 'organizer'
                    ]
                ],
                'links',
                'meta'
            ]);

        $this->assertCount(10, $response->json('data')); // Par défaut 10 par page
    }

    public function test_authenticated_user_can_create_event()
    {
        $eventData = [
            'title' => 'Conférence Tech',
            'description' => 'Une super conférence sur la technologie',
            'event_date' => Carbon::now()->addDays(7)->toDateTimeString(),
            'location' => 'Paris',
            'max_participants' => 100,
            'category_id' => $this->category->id
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Conférence Tech',
                'description' => 'Une super conférence sur la technologie',
                'location' => 'Paris',
                'max_participants' => 100,
                'user_id' => $this->user->id,
                'category_id' => $this->category->id
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Conférence Tech',
            'user_id' => $this->user->id
        ]);
    }

    public function test_unauthenticated_user_cannot_create_event()
    {
        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'event_date' => Carbon::now()->addDays(7)->toDateTimeString(),
            'location' => 'Test Location',
            'max_participants' => 50,
            'category_id' => $this->category->id
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(401);
    }

    public function test_cannot_create_event_with_invalid_data()
    {
        $invalidEventData = [
            'title' => '',
            'description' => '',
            'event_date' => 'invalid-date',
            'location' => '',
            'max_participants' => 0,
            'category_id' => 999 // Non-existent category
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/events', $invalidEventData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title', 'description', 'event_date', 
                'location', 'max_participants', 'category_id'
            ]);
    }

    public function test_can_show_single_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description
            ])
            ->assertJsonStructure([
                'id', 'title', 'description', 'event_date',
                'location', 'max_participants', 'category', 'organizer'
            ]);
    }

    public function test_returns_404_for_nonexistent_event()
    {
        $response = $this->getJson('/api/events/999');

        $response->assertStatus(404);
    }

    public function test_owner_can_update_their_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $updateData = [
            'title' => 'Titre Modifié',
            'description' => 'Description modifiée',
            'event_date' => Carbon::now()->addDays(10)->toDateTimeString(),
            'location' => 'Lyon',
            'max_participants' => 150,
            'category_id' => $this->category->id
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/events/{$event->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $event->id,
                'title' => 'Titre Modifié',
                'description' => 'Description modifiée',
                'location' => 'Lyon',
                'max_participants' => 150
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Titre Modifié'
        ]);
    }

    public function test_non_owner_cannot_update_event()
    {
        $anotherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $anotherUser->id,
            'category_id' => $this->category->id
        ]);

        $updateData = [
            'title' => 'Tentative de modification',
            'description' => 'Description',
            'event_date' => Carbon::now()->addDays(10)->toDateTimeString(),
            'location' => 'Location',
            'max_participants' => 100,
            'category_id' => $this->category->id
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/events/{$event->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Non autorisé.'
            ]);
    }

    public function test_owner_can_delete_their_event()
    {
        $event = Event::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Événement supprimé'
            ]);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id
        ]);
    }

    public function test_non_owner_cannot_delete_event()
    {
        $anotherUser = User::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $anotherUser->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Non autorisé.'
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id
        ]);
    }

    public function test_can_search_events_by_title()
    {
        Event::factory()->create([
            'title' => 'Conférence PHP',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        Event::factory()->create([
            'title' => 'Workshop JavaScript',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson('/api/events/search?search=PHP');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Conférence PHP');
    }

    public function test_can_search_events_by_category()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Event::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category1->id
        ]);

        Event::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id
        ]);

        $response = $this->getJson("/api/events/search?category_id={$category2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_search_returns_404_when_no_events_found()
    {
        $response = $this->getJson('/api/events/search?search=inexistant');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Aucun événement trouvé.'
            ]);
    }

    public function test_can_get_event_places_summary()
    {
        // Cette fonction utilise EventSummary, vous devrez créer une vue ou adapter le test
        // selon votre implémentation de EventSummary
        $this->markTestSkipped('EventSummary view needs to be implemented first');
    }
}
