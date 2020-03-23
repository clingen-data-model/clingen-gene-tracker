<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Services\KafkaConfig;
use App\Services\KafkaProducer;
use App\Services\MessageLogger;
use App\Contracts\MessagePusher;
use App\Services\DisabledPusher;
use Illuminate\Events\Dispatcher;
use App\Contracts\MessageConsumer;
use App\Services\KafkaConsumer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        date_default_timezone_set('America/New_York');
        if ($this->app->environment('production')) {
            config(['backpack.base.skin'=>'skin-blue']);
        }
        if ($this->app->environment('local', 'demo')) {
            config(['backpack.base.logo_lg' => '<b>ClinGen</b> - '.$this->app->environment()]);
        }

        $this->bindInstances();

        \Request::macro('dateParsed', function (...$dates) {
            return collect($this->all())
                    ->transform(function ($value, $key) use ($dates) {
                        if (in_array($key, $dates)) {
                            return Carbon::parse($value);
                        }

                        return $value;
                    })
                    ->toArray();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local', 'testing')) {
            // $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
            // $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    private function bindInstances()
    {
        $this->app->bind(MessagePusher::class, function () {
            if (! config('streaming-service.enable-push')) {
                return new DisabledPusher();
            }
            if (config('streaming-service.driver') == 'log') {
                return new MessageLogger();
            }
            return $this->app->make(KafkaProducer::class);
        });

        $this->app->bind(\RdKafka\Producer::class, function () {
            return new \RdKafka\Producer((new KafkaConfig())());
        });

        $this->app->bind(\RdKafka\KafkaConsumer::class, function () {
            $conf = (new KafkaConfig())();

            // $topicConf = new \RdKafka\TopicConf();
            // $conf->setDefaultTopicConf($topicConf);
            $conf->set('auto.offset.reset', 'smallest');

            return new \RdKafka\KafkaConsumer($conf);
        });

        $this->app->bind(MessageConsumer::class, function () {
            return $this->app->make(KafkaConsumer::class)->addTopic(config('streaming-service.gci-topic'));
        });
    }
}
