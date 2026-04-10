<?php

use App\Broadcasting\PrivateUserChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-user.{id}', PrivateUserChannel::class);
