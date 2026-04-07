<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->latest()->get();
        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'is_default' => 'sometimes|boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Address::where('user_id', $request->user()->id)
                   ->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($request->all());

        return response()->json($address, 201);
    }

    public function show(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($address);
    }

    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'recipient_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:255',
            'province' => 'sometimes|required|string|max:255',
            'postal_code' => 'sometimes|required|string|max:10',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($request->is_default) {
            Address::where('user_id', $request->user()->id)
                   ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json($address);
    }

    public function destroy(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->delete();
        return response()->json(['message' => 'Address deleted successfully']);
    }

    public function setDefault(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Address::where('user_id', $request->user()->id)
               ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json($address);
    }
}