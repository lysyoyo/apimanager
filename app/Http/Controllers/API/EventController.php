<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventSummary;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::with('category', 'organizer')->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'event_date'       => 'required|date',
            'location'         => 'required|string',
            'max_participants' => 'required|integer|min:1',
            'category_id'      => 'required|exists:categories,id',
        ]);

        $event = Event::create([
            'title'            => $request->title,
            'description'      => $request->description,
            'event_date'       => $request->event_date,
            'location'         => $request->location,
            'max_participants' => $request->max_participants,
            'category_id'      => $request->category_id,
            'user_id'          => $request->user()->id, // organisateur
        ]);

        return response()->json($event, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Event::with('category', 'organizer')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'required|string',
            'event_date'       => 'required|date',
            'location'         => 'required|string',
            'max_participants' => 'required|integer|min:1',
            'category_id'      => 'required|exists:categories,id',
        ]);

        $event->update($request->only([
            'title', 'description', 'event_date',
            'location', 'max_participants', 'category_id',
        ]));

        return response()->json($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Événement supprimé']);
    }

    public function search(Request $request)
    {
        try {
            $query = Event::with('category', 'organizer');

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%")
                        ->orWhere('location', 'like', "%$search%");
                });
            }

            if ($request->filled('event_date')) {
                $query->whereDate('event_date', $request->input('event_date'));
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            $events = $query->paginate(10);

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Aucun événement trouvé.'], 404);
            }

            return response()->json($events);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la recherche.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Fonction admin
    public function places($id)
    {
        $summary = EventSummary::find($id);

        if (! $summary) {
            return response()->json(['message' => 'Événement non trouvé.'], 404);
        }

        return response()->json([
            'event_id'           => $summary->event_id,
            'title'              => $summary->title,
            'max_participants'   => $summary->max_participants,
            'total_reservations' => $summary->total_reservations,
            'remaining_spots'    => $summary->remaining_spots,
        ]);
    }
}
