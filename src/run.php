#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Swoole\Coroutine;
use Swoole\HTTP\Request;
use Swoole\HTTP\Response;
use Swoole\Http\Server;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


const CHUNK_SIZE = 100 * 1024 * 1024; // 100K)

// Сообщаем Swoole использовать все возможные асинхронные возможности
Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);

function emit(Response $response, StreamInterface|null $body): void
{
    $body?->rewind();
    if ($body?->getSize() > CHUNK_SIZE) {
        while (!$body->eof()) {
            $response->write($body->read(CHUNK_SIZE));
        }
        $response->end();
    } else {
        $response->end($body->getContents());
    }
}

try {
    // Создание и конфигурация контейнера зависимостей
    $createContainerBuilder = function (): ContainerBuilder {
        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load(__DIR__ . '/../config/services.yaml');
        $containerBuilder->compile(true);
        return $containerBuilder;
    };

    $containerBuilder = $createContainerBuilder();
    $server = $containerBuilder->get('server');

    // Обработка входящих HTTP-запросов
    $server->on('request', function (Request $request, Response $response) use (&$containerBuilder) {
        $method = $request->getMethod();
        foreach ([
                     'Access-Control-Allow-Origin' => '*',
                     'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST, PATCH, DELETE, HEAD',
                     'Access-Control-Allow-Headers' => '*',
                     'Access-Control-Expose-Headers' => 'Location',
                     'Access-Control-Max-Age' => '86400'
                 ] as $name => $value) {
            $response->header($name, $value);
        }
        $body = null;
        if ($method !== 'OPTIONS') {
            // get acc to s3
            $s3Client = $containerBuilder->get('client.s3');
            $bucket = $containerBuilder->getParameter('s3.bucket');

            // get file name
            $pathArray = explode('/', $request->server['path_info']);
            if ($fileName = end($pathArray)) {
                // Read contents
                $file = $s3Client->getObject([
                    'Bucket' => $bucket,
                    'Key' => $fileName,
                ]);

                $body = Utils::streamFor($file->get('Body'));
                $contentType = $file->get('ContentType');
                $response->header('Content-Type', $contentType);
                $response->status(200);
            } else {
                $response->setStatusCode(400);
            }
        }
        emit($response, $body);

    });
    $server->start();
} catch (Throwable $t) {
    // Выводим любые исключения
    print_r([$t->getMessage(), [
        'file' => $t->getFile(),
        'line' => $t->getLine(),
        'stack' => $t->getTrace()
    ]]);
}
