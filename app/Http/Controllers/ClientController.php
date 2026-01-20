<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    // Show all clients (paginated)
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $clients = Client::orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('clients.index', compact('clients', 'perPage'));
    }

    // Show form to create new client
    public function create()
    {
        return view('clients.create');
    }

    // Store new client
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email_address'  => 'required|email|unique:clients,email_address',
            'contact_number' => 'required|string|max:50',
            'payment_terms'  => 'nullable|string|max:255',
        ]);

        Client::create($data);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client added successfully');
    }

    // Show client details
    public function show($id)
    {
        $client = Client::findOrFail($id);

        return view('clients.show', compact('client'));
    }

    // Show form to edit client
    public function edit($id)
    {
        $client = Client::findOrFail($id);

        return view('clients.edit', compact('client'));
    }

    // Update client
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email_address'  => 'required|email|unique:clients,email_address,' . $client->id,
            'contact_number' => 'required|string|max:50',
            'payment_terms'  => 'nullable|string|max:255',
        ]);

        $client->update($data);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client updated successfully');
    }

    // Delete client
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client deleted successfully');
    }
}
