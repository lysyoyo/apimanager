<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS event_summary_view");
    }
};
