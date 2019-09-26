<?php

namespace Tests;

use Mockery;
use App\Services\KafkaProducer;
use App\Contracts\MessagePusher;
use App\Jobs\Curation\AugmentWithHgncInfo;
use App\Jobs\Curation\AugmentWithMondoInfo;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $mock = Mockery::mock(MessagePusher::class)->shouldIgnoreMissing();
        $this->instance(MessagePusher::class, $mock);
        \Event::fake([
            \App\Events\Curation\Saved::class
        ]);
    }
    

    public function callApiAs($user, $method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        return $this->actingAs($user, 'api')
            ->call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null);
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
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

    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
