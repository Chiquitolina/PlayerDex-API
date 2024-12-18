<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use App\Enums\PlayerSkill;
use App\Enums\PlayerPosition;
use App\Models\Player;

use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{

    public function processTeam(Request $request)
    {
        try {
            $validatedData = $request->validate([
                '*.position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
                '*.mainSkill' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerSkill::cases()))],
                '*.numberOfPlayers' => 'required|integer|min:1',
            ]);

            $combinations = collect($validatedData)->map(function ($item) {
                return $item['position'] . '|' . $item['mainSkill'];
            });

            if ($combinations->duplicates()->isNotEmpty()) {
                return response()->json([
                    'error' => 'There are duplicate combinations of position and main skill in the request.'
                ], 400);
            }

            $responses = [];

            foreach ($validatedData as $requirement) {
                $position = $requirement['position'];
                $mainSkill = $requirement['mainSkill'];
                $numberOfPlayers = $requirement['numberOfPlayers'];

                $playersInPosition = Player::where('position', $position)->get();

                if ($playersInPosition->count() < $numberOfPlayers) {
                    return response()->json([
                        'error' => "There are not enough players available in the position '{$position}'."
                    ], 400);
                }

                $players = Player::where('position', $position)
                    ->with(['skills' => function ($query) use ($mainSkill) {
                        $query->where('skill', $mainSkill);
                    }])
                    ->get();

                $filteredPlayers = $players->filter(function ($player) {
                    return $player->skills->isNotEmpty();
                });

                $selectedPlayers = $filteredPlayers->sortByDesc(function ($player) use ($mainSkill) {
                    return $player->getRelationValue('skills')->first()->value;
                })->take($numberOfPlayers);

                foreach ($selectedPlayers as $player) {
                    $responses[] = [
                        'name' => $player->name,
                        'position' => $player->position,
                        'playerSkills' => $player->skills->map(function ($skill) {
                            return [
                                'skill' => $skill->skill,
                                'value' => $skill->value,
                            ];
                        }),
                    ];
                }
            }

            return response()->json($responses, 200);
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
}
