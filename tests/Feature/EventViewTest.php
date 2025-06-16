<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_summary_view_is_accessible()
    {
        // Prépare des données factices si nécessaire
        DB::table('events')->insert([
            'id' => 1,
            'title' => 'Test Event',
            'description' => '...',
            'event_date' => now(),
            'location' => 'Test City',
            'max_participants' => 100,
            'category_id' => 1,
            'user_id' => 1,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Appelle la vue
        $rows = DB::table('event_summary_view')->get();

        // Vérifie qu’il n’y a pas d’erreur et que la vue retourne des lignes
        $this->assertNotNull($rows);
    }
}
