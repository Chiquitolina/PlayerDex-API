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
            $players = Player::with('skills')->get();

            $formattedPlayers = $players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'position' => $player->position,
                    'playerSkills' => $player->getRelationValue('skills')->map(function ($skill) {
                        return [
                            'id' => $skill->id,
                            'skill' => $skill->skill,
                            'value' => $skill->value,
                            'playerId' => $skill->player_id,
                        ];
                    }),
                ];
            });

            return response()->json($formattedPlayers, 200);
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
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
                'playerSkills' => 'required|array',
                'playerSkills.*.skill' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerSkill::cases()))],
                'playerSkills.*.value' => 'required|integer|min:0|max:100',
            ]);

            $player = Player::create([
                'name' => $validatedData['name'],
                'position' => $validatedData['position'],
            ]);

            $playerSkills = collect($validatedData['playerSkills'])->map(function ($skillData) use ($player) {
                $skill = $player->skills()->create([
                    'skill' => $skillData['skill'],
                    'value' => $skillData['value'],
                ]);

                return [
                    'id' => $skill->id,
                    'skill' => $skill->skill,
                    'value' => $skill->value,
                    'playerId' => $skill->player_id,
                ];
            })->toArray();

            return response()->json([
                'id' => $player->id,
                'name' => $player->name,
                'position' => $player->position,
                'playerSkills' => $playerSkills,
            ], 201);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            $firstErrorField = array_key_first($errors);
            $firstErrorValue = $request->input($firstErrorField);
            $firstErrorMessages = $errors[$firstErrorField];

            if (is_array($firstErrorValue)) {
                $firstErrorValue = json_encode($firstErrorValue);
            }

            return response()->json([
                'message' => "Invalid value for {$firstErrorField}: {$firstErrorValue}",
                'errors' => [
                    $firstErrorField => $firstErrorMessages,
                ]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
                'playerSkills' => 'required|array',
                'playerSkills.*.skill' => ['required', 'string', Rule::in(array_map(fn($skill) => $skill->value, PlayerSkill::cases()))],
                'playerSkills.*.value' => 'required|integer|min:0|max:100',
            ]);

            $player = Player::findOrFail($id);

            $player->update([
                'name' => $validatedData['name'],
                'position' => $validatedData['position'],
            ]);

            $playerSkills = collect($validatedData['playerSkills'])->map(function ($skillData) use ($player) {
                $skill = $player->skills()->updateOrCreate(
                    ['skill' => $skillData['skill']],
                    ['value' => $skillData['value']]
                );

                return [
                    'id' => $skill->id,
                    'skill' => $skill->skill,
                    'value' => $skill->value,
                    'playerId' => $skill->player_id,
                ];
            })->toArray();

            return response()->json([
                'id' => $id,
                'name' => $player->name,
                'position' => $player->position,
                'playerSkills' => $playerSkills,
            ], 200);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            $firstErrorField = array_key_first($errors);
            $firstErrorValue = $request->input($firstErrorField);
            $firstErrorMessages = $errors[$firstErrorField];

            if (is_array($firstErrorValue)) {
                $firstErrorValue = json_encode($firstErrorValue);
            }

            return response()->json([
                'message' => "Invalid value for {$firstErrorField}: {$firstErrorValue}",
                'errors' => [
                    $firstErrorField => $firstErrorMessages,
                ]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
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

            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
