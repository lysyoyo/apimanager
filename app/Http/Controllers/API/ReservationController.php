<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\EventSummary;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->reservations()
            ->with('event')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'event_id' => 'required|exists:events,id',
            ]);

            $event = Event::findOrFail($request->event_id);

            if ($request->user()->reservations()->where('event_id', $event->id)->exists()) {
                return response()->json(['message' => 'Vous êtes déjà inscrit à cet événement.'], 409);
            }

            $summary = EventSummary::find($event->id);

            if (! $summary) {
                return response()->json(['message' => 'Résumé de l\'événement introuvable.'], 404);
            }

            if ($summary->remaining_spots <= 0) {
                return response()->json(['message' => 'Événement complet.'], 403);
            }

            $reservation = Reservation::create([
                'user_id'  => $request->user()->id,
                'event_id' => $event->id,
            ]);

            return response()->json([
                'message'     => 'Réservation effectuée avec succès.',
                'reservation' => $reservation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la réservation.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reservation = Reservation::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (! $reservation) {
                return response()->json(['message' => 'Réservation non trouvée.'], 404);
            }

            $reservation->delete();

            return response()->json(['message' => 'Réservation annulée avec succès.']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de l’annulation de la réservation.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function byEvent($eventId)
    {
        $event = Event::findOrFail($eventId);

        $reservations = $event->reservations()->with('user')->get();

        return response()->json([
            'event' => $event->title,
            'total_reservations' => $reservations->count(),
            'participants' => $reservations
        ]);
    }

}
