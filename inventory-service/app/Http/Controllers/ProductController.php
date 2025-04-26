<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductController extends Controller
{
    protected Client $db;
    protected Collection $collection;

    public function __construct()
    {
        $this->db = new Client(config('services.mongodb.uri'));
        $this->collection = $this->db->inventory->products;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $products = $this->collection->find()->toArray();
            return response()->json($products, Response::HTTP_OK);
        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $valData = $request->validate([
            "name" => "required|string|max:100",
            "description" => "required|string|max:1000",
            "price" => "required|numeric|min:0",
            "category" => "required|string|max:100",
            "available" => "required|boolean",
            "ingredients" => "required|array",
            "quantity" => "required|integer|min:0",
        ]);

        try {
            $exists = $this->collection->findOne(['name' => $valData['name']]);
            if ($exists) {
                return response()->json(['error' => 'Product already exists.'], Response::HTTP_CONFLICT);
            }
            $data = [
                'name' => $valData["name"],
                'description' => $valData["description"],
                'price' => $valData["price"],
                'category' => $valData["category"],
                'available' => $valData["available"],
                'ingredients' => $valData["ingredients"],
                'quantity' => $valData["quantity"],
            ];

            $product = $this->collection->insertOne($data);
            $data['_id'] = $product->getInsertedId();

            return response()->json([
                "message" => "Guardado exitosamente",
                "product" => $data
            ], Response::HTTP_CREATED);

        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->collection->findOne(['_id' => new ObjectId($id)]);
            if (!$product) {
                return response()->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json($product, Response::HTTP_OK);
        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $valData = $request->validate([
            "name" => "required|string|max:100",
            "description" => "required|string|max:1000",
            "price" => "required|numeric|min:0",
            "category" => "required|string|max:100",
            "available" => "required|boolean",
            "ingredients" => "required|array",
            "quantity" => "required|integer|min:0",

        ]);

        try {
            $product = $this->collection->updateOne(
                ["_id" => new ObjectId($id)],
                ['$set' => $valData]
            );

            if ($product->getMatchedCount() === 0) {
                return response()->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(["message" => "Actualizado exitosamente"], Response::HTTP_OK);

        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            if ($product->getDeletedCount() === 0) {
                return response()->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json(["message" => "Eliminado exitosamente"], Response::HTTP_OK);
        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search by name
     */
    public function searchByName(Request $request): JsonResponse
    {
        $request->validate([
            "name" => "required|string|max:100",
        ]);
        try {
            $name = $request->input('name');
            $products = $this->collection->find([
                "name" => ['$regex' => '.*' . preg_quote($name, '/') . '.*', '$options' => 'i'],
            ])->toArray();

            if (empty($products)) {
                return response()->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json([
                "message" => "Productos encontrados",
                "products" => $products
            ], Response::HTTP_OK);
        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
