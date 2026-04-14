<?php

namespace LarAIgent\AiKit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LarAIgent\AiKit\Services\HealthCheck;

class HealthController
{
    public function __invoke(Request $request, HealthCheck $healthCheck): JsonResponse
    {
        $deep = $request->boolean('deep', false);
        $result = $healthCheck->run($deep);

        $status = $result['status'] === 'unhealthy' ? 503 : 200;

        return response()->json($result, $status);
    }
}
