<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Contracts\IdempotencyStoreInterface;
use Nutandc\NepalPaymentSuite\Http\LaravelHttpClient;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\GatewayFactory;
use Nutandc\NepalPaymentSuite\Services\GatewayManager;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LaravelCacheStore;
use Nutandc\NepalPaymentSuite\Services\LoggerService;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Psr\Log\LoggerInterface;

final class NepalPaymentSuiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/nepal-payment-suite.php', ConfigKeys::PACKAGE);

        $this->app->singleton(HttpClientInterface::class, function (Application $app): HttpClientInterface {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            return new LaravelHttpClient(
                $app->make(HttpFactory::class),
                (int) $config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::HTTP_TIMEOUT, 10),
            );
        });

        $this->app->singleton(LoggerService::class, function (Application $app): LoggerService {
            return new LoggerService(
                $app->make(LoggerInterface::class),
                $app->make(ConfigRepository::class),
            );
        });

        $this->app->singleton(IdempotencyStoreInterface::class, function (Application $app): IdempotencyStoreInterface {
            return new LaravelCacheStore($app->make(CacheRepository::class));
        });

        $this->app->singleton(IdempotencyService::class, function (Application $app): IdempotencyService {
            return new IdempotencyService(
                $app->make(IdempotencyStoreInterface::class),
                $app->make(ConfigRepository::class),
                $app->make(LoggerService::class),
            );
        });

        $this->app->singleton(EndpointResolver::class);
        $this->app->singleton(SignatureService::class);
        $this->app->singleton(GatewayFactory::class);
        $this->app->singleton(GatewayManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/nepal-payment-suite.php' => config_path('nepal-payment-suite.php'),
        ], 'nepal-payment-suite-config');
    }
}
