<?php


// /////////////////////////////////////////////////////////////////////////////
// TESTING AREA
// THIS IS AN AREA WHERE YOU CAN TEST YOUR WORK AND WRITE YOUR TESTS
// /////////////////////////////////////////////////////////////////////////////

namespace Tests\Feature;


class PlayerControllerCreateTest extends PlayerControllerBaseTest
{
    public function test_sample()
    {
        $data = [
            "name" => "test",
            "position" => "defender",
            "playerSkills" => [
                0 => [
                    "skill" => "attack",
                    "value" => 60
                ],
                1 => [
                    "skill" => "speed",
                    "value" => 80
                ]
            ]
        ];

        $res = $this->postJson(self::REQ_URI, $data);

        $this->assertNotNull($res);
    }

    public function test_position_is_invalid_when_number_is_provided_returns_422()
    {
        $data = [
            "name" => "test",
            "position" => 123,
            "playerSkills" => [
                0 => [
                    "skill" => "attack",
                    "value" => 60
                ],
                1 => [
                    "skill" => "speed",
                    "value" => 80
                ]
            ]
        ];

        $res = $this->postJson(self::REQ_URI, $data);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['position']);
    }

    public function test_player_skill_is_invalid_when_name_is_missing_returns_422()
    {
        $data = [
            "name" => "test",
            "position" => "defender",
            "playerSkills" => [
                0 => [
                    "skill" => "",
                    "value" => 60
                ],
                1 => [
                    "skill" => "speed",
                    "value" => 80
                ]
            ]
        ];

        $res = $this->postJson(self::REQ_URI, $data);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['playerSkills.0.skill']);
    }

    public function test_player_skill_is_invalid_when_value_is_missing_returns_422()
    {
        $data = [
            "name" => "test",
            "position" => "defender",
            "playerSkills" => [
                0 => [
                    "skill" => "attack",
                    "value" => 60
                ],
                1 => [
                    "skill" => "speed",
                    "value" => null
                ]
            ]
        ];

        $res = $this->postJson(self::REQ_URI, $data);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['playerSkills.1.value']);
    }
}
