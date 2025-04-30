<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*/**
config('services.auth.url')
config('services.orders.url')
config('services.inventory.url')
*/

class GatewayController extends Controller
{
    const PREFIX_V1 = '/api/v1/';

    public function forwardToAuth(Request $request)
    {
        $path = $this->stripApiPrefix($request->path());
        $response = Http::post(config('services.auth.url') . self::PREFIX_V1 . $path, $request->all());
        return response($response->body(), $response->status());
    }

    public function forwardToInventory(Request $request, $any = null)
    {
        $anyPath = $this->formatAnyPath($any);

        $response = Http::withToken($request->bearerToken())
            ->send(
                $request->method(),
                config('services.inventory.url') . self::PREFIX_V1 . 'products' . $anyPath,
                [
                    'query' => $request->query(),
                    'json' => $request->all(),
                ]
            );

        return response($response->body(), $response->status());
    }

    public function forwardToOrders(Request $request, $any = null)
    {
        $anyPath = $this->formatAnyPath($any);

        $response = Http::withToken($request->bearerToken())
            ->send(
                $request->method(),
                config('services.orders.url') . self::PREFIX_V1 . 'orders' . $anyPath,
                [
                    'query' => $request->query(),
                    'json' => $request->all(),
                ]
            );

        return response($response->body(), $response->status());
    }

    public function forwardToEmails(Request $request, $any = null)
    {
        $anyPath = $this->formatAnyPath($any);
        $response = Http::withToken($request->bearerToken())
            ->send(
                $request->method(),
                config('services.emails.url') . self::PREFIX_V1 . 'emails' . $anyPath,
                [
                    'query' => $request->query(),
                    'json' => $request->all(),
                ]
            );

        return response($response->body(), $response->status());
    }

    private function stripApiPrefix(string $path): string
    {
        return preg_replace('#^api(?:/v\d+)?/?#', '', $path);
    }

    public function formatAnyPath($any): string
    {
        return !empty($any) ? '/' . $any : '';
    }
}
