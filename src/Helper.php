<?php

declare(strict_types=1);

namespace Bunny;

use React\EventLoop\Loop;
use function React\Async\async;
use function React\Async\await;
use function React\Promise\Timer\sleep;
use function React\Promise\race;

final class Helper
{
    private const TIMEOUT = 10.0;

    /**
     * Open connection, published message to given exchange, and close the connection before returning.
     *
     * @param array<string,mixed> $headers
     *
     * @return int|false
     */
    public static function publish(
        Configuration $configuration,
        string $body,
        array $headers = [],
        string $exchange = '',
        string $routingKey = '',
        bool $mandatory = false,
        bool $immediate = false,
        float $timeout = self::TIMEOUT,
    ): int|bool {
        $promises = [];
        $promises[] = async(static function (
            Configuration $configuration,
            string $body,
            array $headers = [],
            string $exchange = '',
            string $routingKey = '',
            bool $mandatory = false,
            bool $immediate = false,
        ): int|bool {
            $client = new Client($configuration);
            $channel = $client->channel();
            $outCome = $channel->publish($body, $headers, $exchange, $routingKey, $mandatory, $immediate);
            Loop::futureTick(async(static function () use ($client): bool {
                $client->disconnect();

                return true;
            }));

            return $outCome;
        })(
            $configuration,
            $body,
            $headers,
            $exchange,
            $routingKey,
            $mandatory,
            $immediate,
        );
        $promises[] = sleep($timeout);

        return await(race($promises));
    }
}
