<?php

namespace Tests\Unit;

use App\Broadcasting\PrivateUserChannel;
use App\Models\User;
use Tests\TestCase;

class PrivateUserChannelTest extends TestCase
{
    public function test_it_authorizes_the_owner_of_the_channel(): void
    {
        $user = new User();
        $user->id = 10;
        $channel = new PrivateUserChannel();

        $this->assertTrue($channel->join($user, 10));
    }

    public function test_it_denies_access_for_non_owner(): void
    {
        $user = new User();
        $user->id = 10;
        $channel = new PrivateUserChannel();

        $this->assertFalse($channel->join($user, 99));
    }
}
