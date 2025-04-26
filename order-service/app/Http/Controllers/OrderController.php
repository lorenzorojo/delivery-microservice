<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    protected Client $db;
    protected Collection $collection;

    public function __construct()
    {
        $this->db = new Client(config('services.mongodb.uri'));
        $this->collection = $this->db->orders_services_db->orders;
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
    public function store(Request $request)
    {
        $valData = $request->validate([
            'customer_name' => 'required|string|max:100',
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.name' => 'required|string|max:100',
            'items.*.description' => 'required|string|max:1000',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.category' => 'required|string|max:100',
            'items.*.available' => 'required|boolean',
            'items.*.ingredients' => 'required|array',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,completed,canceled'
        ]);

        $updatedProducts = [];

        try {
            $token = $request->header('Authorization');
            $token = str_replace("Bearer ", "", $token);
            $inventoryService = config('services.inventory.url');

            foreach ($valData["items"] as $item) {
                $inventoryResponse = Http::withToken($token)
                    ->timeout(90)
                    ->get($inventoryService . "/api/v1/products/{$item['product_id']}");

                if ($inventoryResponse->failed() || $inventoryResponse->status() === Response::HTTP_NOT_FOUND) {
                    return response()->json(["error" => "Product not found"], Response::HTTP_NOT_FOUND);
                }

                $product = $inventoryResponse->json();

                if ($product['quantity'] < $item['quantity']) {
                    return response()->json(["error" => "No hay suficiente stock"], Response::HTTP_NOT_FOUND);
                }

                $item["quantity"] = $product["quantity"] - $item["quantity"];
                $updatedProducts[] = $item;
            }

            foreach ($updatedProducts as $toUpdate) {
                $updatedResponse = Http::withToken($token)
                    ->timeout(90)
                    ->put($inventoryService . "/api/v1/products/{$item['product_id']}", $toUpdate);

                if ($updatedResponse->failed()) {
                    Log::error('',[$toUpdate,$updatedResponse]);
                    return response()->json(["error" => "Error al actualizar"], Response::HTTP_NOT_FOUND);
                }
            }

            $data = [
                'customer_name' => $valData["customer_name"],
                'items' => $valData["items"],
                'total_price' => $valData["total_price"],
                'status' => $valData["status"],
                'created_at' => new UTCDateTime(),
                'updated_at' => new UTCDateTime(),
            ];

            $order = $this->collection->insertOne($data);
            $data['_id'] = $order->getInsertedId();

            # Email Service
            $emailService = config('services.email.url');
            $emailResponse = Http::withToken($token)->timeout(90)
                ->post($emailService . "/api/v1/emails",[
                    "from" => "no-reply@delivery-order-service.com",
                    "to" => $valData["customer_email"] ?? "customer-email@delivery-order-service.com",
                    "subject" => "Confirmación de pedido #{$data['_id']}",
                    "content" => "Hola {$valData["customer_name"]}!. Gracias pos su compra, su pedido ha sido recibido.",
                    "order" => $data,
                ]);

            if ($emailResponse->failed()) {
                Log::error("Error enviando el email",[$emailResponse->getBody()]);
                return response()->json(["error" => "Error enviando el email"], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                "message" => "Orden enviada con exitosamente",
                "order" => $data
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
            $order = $this->collection->findOne(['_id' => new ObjectId($id)]);
            if (!$order) {
                return response()->json(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json($order, Response::HTTP_OK);
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
            "customer_name" => "sometimes|required|string|max:100",
            "items" => "sometimes|required|array",
            "total_price" => "sometimes|required|numeric|min:0",
            "status" => "sometimes|required|string|in:pending,completed,canceled",
        ]);

        try {
            $updateDate = array_filter($valData);

            if (empty($updateDate)) {
                return response()->json(['error' => 'No se encontraron datos para actualizar.'], Response::HTTP_BAD_REQUEST);
            }

            $order = $this->collection->updateOne(
                ["_id" => new ObjectId($id)],
                ['$set' => $updateDate]
            );

            if ($order->getMatchedCount() === 0) {
                return response()->json(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
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
            $order = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            if ($order->getDeletedCount() === 0) {
                return response()->json(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json(["message" => "Eliminado exitosamente"], Response::HTTP_OK);
        } catch (Throwable $th) {
            report($th);
            return response()->json([
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
