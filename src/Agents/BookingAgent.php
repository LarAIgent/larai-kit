<?php

namespace LarAIgent\AiKit\Agents;

use LarAIgent\AiKit\Tools\BookAppointmentTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class BookingAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a booking assistant. When the user wants to schedule an appointment, use the booking tool.';
    }

    /**
     * @return array<int, \Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new BookAppointmentTool(),
        ];
    }
}
