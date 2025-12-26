<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Client;
use App\Models\SecurityGuard;

class EventController extends Controller
{
    // Show the create form
    public function create()
    {
        $clients = Client::all();      // For client dropdown
        $guards = SecurityGuard::all(); // For guards multi-select

        return view('events.create', compact('clients', 'guards'));
    }

    // Show all events
    public function index()
    {
        $events = Event::with(['client', 'guards'])->orderBy('created_at', 'desc')->get();
        return view('events.index', compact('events'));
    }

    // Store the event
    public function store(Request $request)
    {

        //dd($request->all());
                    $request->validate([
            'event_name'     => 'required|string|max:255',
            'address'        => 'required|string|max:500',
            'client_id'      => 'required|exists:clients,id',
            'charge_rate'    => 'required|numeric',
            'invoice_date'   => 'required|date',
            'pay_rate'       => 'required|numeric',
            'client_contact' => 'required|string|max:20',
            'instructions'   => 'nullable|string',
            'guards'         => 'nullable|array',
            'guards.*'       => 'exists:security_guards,id',
        ]);

        $event = Event::create($request->only([
            'event_name',
            'address',
            'client_id',
            'charge_rate',
            'invoice_date',
            'pay_rate',
            'client_contact',
            'instructions',
        ]));

        if ($request->has('guards')) {
            $event->guards()->sync(ids: $request->guards);
        }

        return redirect()->back()->with('success', 'Event created successfully!');
    }

    // Show the edit form
    public function edit(Event $event)
    {
        $clients = Client::all();
        $guards = SecurityGuard::all();

        return view('events.edit', compact('event', 'clients', 'guards'));
    }

    // Update the event
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'event_name'     => 'required|string|max:255',
            'address'        => 'required|string|max:500',
            'client_id'      => 'required|exists:clients,id',
            'charge_rate'    => 'required|numeric',
            'invoice_date'   => 'required|date',
            'pay_rate'       => 'required|numeric',
            'client_contact' => 'required|string|max:20',
            'instructions'   => 'nullable|string',
            'guards'         => 'nullable|array',
            'guards.*'       => 'exists:security_guards,id',
        ]);

        $event->update($request->only([
            'event_name',
            'address',
            'client_id',
            'charge_rate',
            'invoice_date',
            'pay_rate',
            'client_contact',
            'instructions',
        ]));

        // Sync assigned guards
        $event->guards()->sync($request->guards ?? []);

        return redirect()->route('events.index')->with('success', 'Event updated successfully!');
    }

    // Show a single event
    public function show(Event $event)
    {
        $event->load(['client', 'guards']); // eager load relations
        return view('events.show', compact('event'));
    }

    // Delete an event
    public function destroy(Event $event)
    {
        // Remove relationships first
        $event->guards()->detach();

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted successfully!');
    }
}
