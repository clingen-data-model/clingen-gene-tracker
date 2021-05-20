<?php

namespace Tests;

use Mockery;
use JsonSerializable;
use App\DataExchange\Contracts\MessagePusher;
use App\Rules\ValidGeneSymbolRule;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected $fakeCurationSavedEvent = true;

    public function setUp(): void
    {
        parent::setUp();
        $mock = Mockery::mock(MessagePusher::class)->shouldIgnoreMissing();
        $this->instance(MessagePusher::class, $mock);
        if ($this->fakeCurationSavedEvent) {
            \Event::fake([
                \App\Events\Curation\Saved::class,
            ]);
        }
    }

    public function callApiAs($user, $method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        return $this->actingAs($user, 'api')
            ->call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null);
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class() extends Handler {
            public function __construct()
            {
            }

            public function report(\Exception $e)
            {
            }

            public function render($request, \Exception $e)
            {
                throw $e;
            }
        });
    }

    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function castToJson($json)
    {
        // Convert from array to json and add slashes, if necessary.
        if (is_array($json)) {
            $json = addslashes(json_encode($json));
        } elseif (is_object($json) && class_implements($json, JsonSerializable::class)) {
            $json = addslashes(json_encode($json));
        }
        // Or check if the value is malformed.
        elseif (is_null($json) || is_null(json_decode($json))) {
            throw new \Exception('A valid JSON string was not provided.');
        }

        return \DB::raw("CAST('{$json}' AS JSON)");
    }

    protected function assumeGeneSymbolValid()
    {
        app()->bind(\App\Rules\ValidGeneSymbolRule::class, function ($app) {
            $stub = $this->createMock(ValidGeneSymbolRule::class);
            $stub->method('passes')
                ->willReturn(true);

            return $stub;
        });
    }
}
