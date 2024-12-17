<?php

// /////////////////////////////////////////////////////////////////////////////
// PLEASE DO NOT RENAME OR REMOVE ANY OF THE CODE BELOW. 
// YOU CAN ADD YOUR CODE TO THIS FILE TO EXTEND THE FEATURES TO USE THEM IN YOUR WORK.
// /////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;

use App\Models\Player;

class PlayerController extends Controller
{
    public function index()
    {
        return response("Failed", 500);
    }

    public function show()
    {
        return response("Failed", 500);
    }

    public function store()
    {
        return response("Failed", 500);
    }

    public function update()
    {
        return response("Failed", 500);
    }

    public function destroy($id)
    {
        try {
            $player = Player::find($id);

            if (!$player) {
                return response("Player not found", 404);
            }

            $player->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {

            return response("Failed to delete player", 500);  // Error interno del servidor
        }
    }
}
