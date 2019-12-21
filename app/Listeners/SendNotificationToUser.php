<?php

namespace App\Listeners;

use App\Events\AssignedDriver;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationToUser
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AssignedDriver  $event
     * @return void
     */
    public function handle(AssignedDriver $event)
    {
        //
    }
}
