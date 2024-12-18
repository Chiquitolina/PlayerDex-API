<?php

// /////////////////////////////////////////////////////////////////////////////
// TESTING AREA
// THIS IS AN AREA WHERE YOU CAN TEST YOUR WORK AND WRITE YOUR TESTS
// /////////////////////////////////////////////////////////////////////////////

namespace Tests\Feature;

class PlayerControllerDeleteTest extends PlayerControllerBaseTest
{

    public function test_sample()
    {
        $res = $this->delete(self::REQ_URI . '1');

        $this->assertNotNull($res);
    }

    public function test_delete_without_bearer_token_returns_401()
    {
        $res = $this->delete(self::REQ_URI . '1');

        $res->assertStatus(401);
    }

    public function test_delete_with_invalid_authorization_token_returns_401()
    {
        $invalidToken = 'invalid_token_example';

        $res = $this->withHeaders([
            'Authorization' => 'Bearer ' . $invalidToken,
        ])->delete(self::REQ_URI . '1');

        $res->assertStatus(401);
    }
}
