<?php

namespace ZendeskSlackUnfurl\Test;

use Psr\Log\NullLogger;
use SlackUnfurl\Event\UnfurlEvent;
use ZendeskSlackUnfurl\Event\Subscriber\ZendeskUnfurler;
use ZendeskSlackUnfurl\ZendeskClient;

class ZendeskTest extends TestCase
{
    public function testUnfurl()
    {
        $ticket_expected = self::setUpResource('tickets');
        $ticket_fields_expected = self::setUpResource('ticket_fields');
        $user_expected = self::setUpResource('users');

        $url = parse_url(self::$server->getServerRoot());
        $domain = "${url['host']}:${url['port']}";
        $client = new ZendeskClient("${url['scheme']}://$domain", 'test', 'test');
        $fields = '1';

        $unfurler = new ZendeskUnfurler($client, compact('domain', 'fields'), new NullLogger());
        $data = [
            'type' => 'link_shared',
            'user' => 'Uxxxxxxxx',
            'channel' => 'Dxxxxxxxx',
            'message_ts' => '1546300800.000000',
            'links' => [
                [
                    'url' => "https://$domain/agent/tickets/1",
                    'domain' => $domain,
                ],
            ],
        ];
        $event = new UnfurlEvent($data);
        $unfurler->unfurl($event);

        $unfurl = reset($event->getUnfurls());
        dump($unfurl);

        $this->assertInternalType('array', $unfurl);

        $this->assertArrayHasKey('title', $unfurl);
        $this->assertArrayHasKey('text', $unfurl);
        $this->assertArrayHasKey('ts', $unfurl);
        $this->assertArrayHasKey('footer', $unfurl);

        $this->assertContains(
          $ticket_expected['subject'],
          $unfurl['title']
        );
        $this->assertEquals(
          $ticket_expected['description'],
          $unfurl['text']
        );
        $this->assertEquals(
          strtotime($ticket_expected['created_at']),
          $unfurl['ts']
        );
        $this->assertContains(
          $user_expected['name'],
          $unfurl['footer']
        );
        $this->assertEquals(
            $ticket_fields_expected[0]['title'],
            $unfurl['fields'][0]['title']
        );
        $this->assertEquals(
            $ticket_fields_expected[0]['custom_field_options'][0]['name'],
            $unfurl['fields'][0]['value']
        );
    }
}
