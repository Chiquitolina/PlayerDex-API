<?php

// /////////////////////////////////////////////////////////////////////////////
// PLEASE DO NOT RENAME OR REMOVE ANY OF THE CODE BELOW. 
// YOU CAN ADD YOUR CODE TO THIS FILE TO EXTEND THE FEATURES TO USE THEM IN YOUR WORK.
// /////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;

use App\Models\Player;

use App\Enums\PlayerPosition;
use App\Enums\PlayerSkill;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlayerController extends Controller
{
    public function index()
    {
        try {

            $players = Player::all();

            return response()->json($players, 200);
        } catch (\Exception $e) {

            return response("Failed", 500);  // Error interno del servidor
        }
    }

    public function show()
    {
        return response("Failed", 500);
    }

    public function store(Request $request)
    {
        try {
            // Validar los datos del jugador
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
                'playerSkills' => 'required|array',
                'playerSkills.*.skill' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerSkill::cases()))],
                'playerSkills.*.value' => 'required|integer|min:0|max:100',
            ]);

            // Crear el jugador
            $player = Player::create([
                'name' => $validatedData['name'],
                'position' => $validatedData['position'],
            ]);

            // Asociar habilidades al jugador
            $playerSkills = [];
            foreach ($validatedData['playerSkills'] as $skillData) {
                $skill = $player->skills()->create([
                    'skill' => $skillData['skill'],
                    'value' => $skillData['value'],
                ]);
                $playerSkills[] = [
                    'id' => $skill->id,
                    'skill' => $skill->skill,
                    'value' => $skill->value,
                    'playerId' => $skill->player_id,
                ];
            }

            // Respuesta personalizada
            return response()->json([
                'id' => $player->id,
                'name' => $player->name,
                'position' => $player->position,
                'playerSkills' => $playerSkills,
            ], 201);
        } catch (ValidationException $e) {
            // Captura la excepción de validación y personaliza la respuesta
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response("Failed", 500);  // Error interno del servidor
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validar los datos de entrada
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
                'playerSkills' => 'required|array',
                'playerSkills.*.skill' => ['required', 'string', Rule::in(array_map(fn($skill) => $skill->value, PlayerSkill::cases()))],
                'playerSkills.*.value' => 'required|integer|min:0|max:100',
            ]);

            // Buscar al jugador
            $player = Player::findOrFail($id);

            // Actualizar el jugador
            $player->update([
                'name' => $validatedData['name'],
                'position' => $validatedData['position'],
            ]);

            // Actualizar o crear las habilidades
            $playerSkills = [];
            foreach ($validatedData['playerSkills'] as $skillData) {
                $skill = $player->skills()->updateOrCreate(
                    ['skill' => $skillData['skill']], // Condición para encontrar la habilidad
                    ['value' => $skillData['value']]  // Datos a actualizar/crear
                );

                $playerSkills[] = [
                    'id' => $skill->id,
                    'skill' => $skill->skill,
                    'value' => $skill->value,
                    'playerId' => $skill->player_id,
                ];
            }

            // Devolver la respuesta en el formato deseado
            return response()->json([
                'id' => $id,
                'name' => $player->name,
                'position' => $player->position,
                'playerSkills' => $playerSkills,
            ], 200);
        } catch (ValidationException $e) {
            // Capturar y formatear el primer error
            $firstErrorField = array_key_first($e->errors());
            $firstErrorValue = $request->input($firstErrorField);
            $firstErrorMessage = $e->errors()[$firstErrorField][0];

            return response()->json([
                'message' => "Invalid value for {$firstErrorField}: {$firstErrorValue}"
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed'], 500);
        }
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

            return response("Failed", 500);  // Error interno del servidor
        }
    }
}
