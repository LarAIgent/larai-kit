<?php

namespace LarAIgent\AiKit\Tests;

use LarAIgent\AiKit\AiKitServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AiKitServiceProvider::class];
    }
}
