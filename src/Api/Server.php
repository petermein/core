<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Foundation\Application;
use Flarum\Http\AbstractServer;
use Tobscure\JsonApi\Document;
use Zend\Stratigility\MiddlewarePipe;

class Server extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        $apiPath = parse_url($app->url('api'), PHP_URL_PATH);

        if ($app->isInstalled() && $app->isUpToDate()) {
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($apiPath, $app->make('Flarum\Api\Middleware\FakeHttpMethods'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\StartSession'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\AuthenticateWithHeader'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\SetLocale'));
            $pipe->pipe($apiPath, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.api.routes')]));
            $pipe->pipe($apiPath, $app->make('Flarum\Api\Middleware\HandleErrors'));
        } else {
            $pipe->pipe($apiPath, function () {
                $document = new Document;
                $document->setErrors([
                    [
                        'code' => 503,
                        'title' => 'Service Unavailable'
                    ]
                ]);

                return new JsonApiResponse($document, 503);
            });
        }

        return $pipe;
    }
}
