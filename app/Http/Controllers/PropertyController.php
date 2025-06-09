<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\PropertyResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PropertyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all properties based on user role
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Jika pembeli, tampilkan semua properti
            if ($user->role === 'pembeli') {
                $properties = Property::latest()->get();
            } else {
                // Jika penjual, hanya tampilkan properti miliknya
                $properties = Property::where('user_id', $user->id)
                    ->latest()
                    ->get();
            }

            return PropertyResource::collection($properties)
                ->response()
                ->setStatusCode(Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch properties: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new property
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:rumah,apartemen,tanah,ruko',
            'description' => 'nullable|string',
            'location' => 'required|string',
        ]);

        try {
            $user = Auth::user();

            // Tambahkan data user_id agar relasi tersimpan
            $property = Property::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'price' => $validated['price'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'],
                'status' => 'pending'
            ]);

            return (new PropertyResource($property))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create property: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a property
     */
    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:rumah,apartemen,tanah,ruko',
            'status' => 'sometimes|in:aktif,pending,terjual',
            'description' => 'nullable|string',
            'location' => 'sometimes|string',
        ]);

        try {
            $property->update($validated);

            return (new PropertyResource($property))
                ->response()
                ->setStatusCode(Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update property: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a property
     */
    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        try {
            $property->delete();

            return response()->json(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete property: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
