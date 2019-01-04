<?php

namespace ZendeskSlackUnfurl\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ZendeskSlackUnfurl\Event\Subscriber\ZendeskUnfurler;
use ZendeskSlackUnfurl\ZendeskClient;

class ZendeskUnfurlServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['zendesk.domain'] = getenv('ZENDESK_DOMAIN');
        $app['zendesk.username'] = getenv('ZENDESK_USERNAME');
        $app['zendesk.token'] = getenv('ZENDESK_TOKEN');

        $app[ZendeskClient::class] = function ($app) {
            return new ZendeskClient($app['zendesk.domain'], $app['zendesk.username'], $app['zendesk.token']);
        };

        $app[ZendeskUnfurler::class] = function ($app) {
            return new ZendeskUnfurler(
                $app[ZendeskClient::class],
                $app['zendesk.domain'],
                $app['logger']
            );
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app[ZendeskUnfurler::class]);
    }
}