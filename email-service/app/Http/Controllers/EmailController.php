<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderShippedEmail;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmailController extends Controller
{
    public function sendOrderShippedEmail(Request $request): JsonResponse
    {

        try {
            $order = $request->input('order');
            $from = $request->input('from');
            $to = $request->input('to');
            $subject = $request->input('subject');
            $content = $request->input('content');

            SendOrderShippedEmail::dispatch($order, $from, $to, $subject, $content);
            return response()->json(['message' => 'Email sent successfully'], ResponseAlias::HTTP_OK);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
