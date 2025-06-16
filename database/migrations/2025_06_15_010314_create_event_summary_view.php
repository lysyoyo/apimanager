<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEventSummaryView extends Migration
{
    public function up()
    {
        // Supprimer la vue si elle existe déjà (optionnel mais propre)
        DB::statement('DROP VIEW IF EXISTS event_summary_view');

        DB::statement("
            CREATE VIEW event_summary_view AS
            SELECT
                e.id AS event_id,
                e.title,
                e.max_participants,
                COUNT(r.id) AS total_reservations,
                (e.max_participants - COUNT(r.id)) AS remaining_spots
            FROM events e
            LEFT JOIN reservations r ON r.event_id = e.id
            GROUP BY e.id, e.title, e.max_participants
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS event_summary_view');
    }
}
