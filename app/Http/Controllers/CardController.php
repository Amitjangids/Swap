<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VirtualCardService;

class CardController extends Controller
{
    public function checkConnection(VirtualCardService $virtualCardService)
    {
        $response = $virtualCardService->checkConnection();

        return response()->json($response);
    }
    public function registerVirtualCard(VirtualCardService $virtualCardService)
    {
        $response = $virtualCardService->registerVirtualCard();

        return response()->json($response);
    }
}
