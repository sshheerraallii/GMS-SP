<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    // Show all clients
    public function index()
    {
        $clients = Client::all();
        return view('clients.index', compact('clients'));
    }

    // Show form to create new client
    public function create()
    {
        $types = ['Vat', 'Non Vat'];
        return view('clients.create', compact('types'));
    }

    // Store new client
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email_address' => 'required|email|unique:clients,email_address',
            'contact_number' => 'required|string|max:50',
            'payment_terms' => 'nullable|string|max:255',
            'type' => 'required|in:VAT,NON-VAT',
        ]);

        Client::create($request->all());

        return redirect()->route('clients.index')->with('success', 'Client added successfully');
    }

    // Show client details
    public function show($id)
    {
        $client = Client::findOrFail($id); // Throws 404 if not found
        return view('clients.show', compact('client'));
    }

    // Show form to edit client
    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $types = ['Vat', 'Non Vat'];
        return view('clients.edit', compact('client', 'types'));
    }

    // Update client
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email_address' => 'required|email|unique:clients,email_address,' . $client->id,
            'contact_number' => 'required|string|max:50',
            'payment_terms' => 'nullable|string|max:255',
            'type' => 'required|in:VAT,NON-VAT',
        ]);

        $client->update($request->all());

        return redirect()->route('clients.index')->with('success', 'Client updated successfully');
    }

    // Delete client
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully');
    }
}
