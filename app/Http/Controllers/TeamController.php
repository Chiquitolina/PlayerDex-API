<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use App\Enums\PlayerSkill;
use App\Enums\PlayerPosition;

use App\Models\Player;

class TeamController extends Controller
{

    public function processTeam(Request $request)
    {
        $validatedData = $request->validate([
            'position' => ['required', 'string', Rule::in(array_map(fn($position) => $position->value, PlayerPosition::cases()))],
            'skill' => ['required', 'string', Rule::in(array_map(fn($skill) => $skill->value, PlayerSkill::cases()))],
            '*.numberOfPlayers' => 'required|integer|min:1'
        ]);

        $responses = [];
        $usedPlayerIds = [];

        foreach ($validatedData as $requirement) {
            $position = $requirement['position'];
            $mainSkill = $requirement['mainSkill'];
            $numberOfPlayers = $requirement['numberOfPlayers'];

            $players = Player::where('position', $position)->with('skills')->get();

            if ($players->count() < $numberOfPlayers) {
                return response()->json([
                    'error' => "Insufficient number of players for position: {$position}"
                ], 400);
            }

            $playersWithSkill = $players->filter(function ($player) use ($mainSkill, $usedPlayerIds) {
                return $player->getRelationValue('skills')->contains('skill', $mainSkill) && !in_array($player->id, $usedPlayerIds);
            });

            $playersWithSkill = $playersWithSkill->sortByDesc(function ($player) use ($mainSkill) {
                return $player->getRelationValue('skills')->firstWhere('skill', $mainSkill)['value'];
            });

            $selectedPlayers = $playersWithSkill->take($numberOfPlayers);

            if ($selectedPlayers->count() < $numberOfPlayers) {
                $remainingPlayersNeeded = $numberOfPlayers - $selectedPlayers->count();

                $remainingPlayers = $players->filter(function ($player) use ($usedPlayerIds) {
                    return !in_array($player->id, $usedPlayerIds);
                })->sortByDesc(function ($player) {
                    return collect($player->getRelationValue('skills'))->max('value');
                });

                $selectedPlayers = $selectedPlayers->merge($remainingPlayers->take($remainingPlayersNeeded));
            }

            $usedPlayerIds = array_merge($usedPlayerIds, $selectedPlayers->pluck('id')->toArray());

            foreach ($selectedPlayers as $player) {
                $responses[] = [
                    'name' => $player->name,
                    'position' => $player->position,
                    'playerSkills' => $player->getRelationValue('skills')->map(function ($skill) {
                        return [
                            'skill' => $skill->skill,
                            'value' => $skill->value,
                        ];
                    }),
                ];
            }
        }

        return response()->json($responses);
    }
}
