<?php

namespace LarAIgent\AiKit\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class BookAppointmentTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Book an appointment by name and date.';
    }

    public function handle(Request $request): Stringable|string
    {
        $name = $request['name'] ?? 'Guest';
        $date = $request['date'] ?? 'unspecified date';
        $notes = $request['notes'] ?? null;

        $message = "Appointment booked for {$name} on {$date}.";

        if (! empty($notes)) {
            $message .= " Notes: {$notes}.";
        }

        return $message;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'date' => $schema->string()->required(),
            'notes' => $schema->string()->nullable(),
        ];
    }
}
