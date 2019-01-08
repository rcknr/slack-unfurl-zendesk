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
        $app['zendesk.fields'] = getenv('ZENDESK_FIELDS');

        $app[ZendeskClient::class] = function ($app) {
          $base_url = sprintf('https://%s/api/v2', $app['zendesk.domain']);

          return new ZendeskClient($base_url, $app['zendesk.username'], $app['zendesk.token']);
        };

        $app[ZendeskUnfurler::class] = function ($app) {
            return new ZendeskUnfurler(
                $app[ZendeskClient::class],
                [
                    'domain' => $app['zendesk.domain'],
                    'fields' => $app['zendesk.fields']
                ],
                $app['logger']
            );
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app[ZendeskUnfurler::class]);
    }
}
