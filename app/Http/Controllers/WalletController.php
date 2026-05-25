<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Retrieve wallets associated with a currency based on the symbol or slug.
     *
     * @param string $symbol
     * @return JsonResponse
     */
    public function getWalletsByCurrency(string $symbol): JsonResponse
    {
        $currency = Currency::where('symbol', $symbol)
            ->orWhere('slug', $symbol)
            ->first();

        if (!$currency) {
            return response()->json([
                'message' => 'Currency not found',
                'success' => false,
                'data' => [],
            ], 404);
        }

        $wallets = $currency->wallets;

        return response()->json([
            'message' => 'Wallets retrieved successfully',
            'success' => true,
            'data' => WalletResource::collection($wallets),
        ]);
    }
}
