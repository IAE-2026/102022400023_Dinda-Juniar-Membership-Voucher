<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RabbitBoardMessage;

class BoardController extends Controller
{
    public function index()
    {
        return view('board.index');
    }

    public function messages(Request $request)
    {
        $limit = min(200, (int) $request->query('limit', 50));
        $msgs  = RabbitBoardMessage::orderBy('received_at', 'desc')->limit($limit)->get();
        return response()->json($msgs);
    }
}
