<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:add,subtract',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        Tax::create($validated);

        return redirect()->back()->with('status', 'Tax created successfully.');
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:add,subtract',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $tax->update($validated);

        return redirect()->back()->with('status', 'Tax updated successfully.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->back()->with('status', 'Tax deleted successfully.');
    }
}
