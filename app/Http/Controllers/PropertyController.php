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
        $this->authorizeResource(Property::class, 'property');
    }

    /**
     * Get all properties owned by authenticated seller
     */
    public function index(): JsonResponse
    {
        try {
            $properties = Property::where('user_id', Auth::id())
                ->latest()
                ->get();

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
     * Create new property
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:rumah,apartemen,tanah,ruko',
            'description' => 'nullable|string',
            'location' => 'required|string'
        ]);

        try {
            $user = Auth::user();
            $property = $user->properties()->create(array_merge(
                $validated,
                ['status' => 'pending']
            ));

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
     * Update property details
     */
    public function update(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:aktif,pending,terjual',
            'description' => 'nullable|string',
            'location' => 'sometimes|string'
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
     * Delete property
     */
    public function destroy(Property $property): JsonResponse
    {
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
