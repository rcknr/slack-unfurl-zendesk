<?php

namespace ZendeskSlackUnfurl\Test;

use donatj\MockWebServer\Response;
use Psr\Log\NullLogger;
use SlackUnfurl\Event\UnfurlEvent;
use ZendeskSlackUnfurl\Event\Subscriber\ZendeskUnfurler;
use ZendeskSlackUnfurl\ZendeskClient;

class ZendeskTest extends TestCase
{
    public function testUnfurl()
    {
        $ticket = file_get_contents(__DIR__ . '/Resources/ticket.json');
        $user = file_get_contents(__DIR__ . '/Resources/user.json');
        $ticket_expected = json_decode($ticket, true);
        $user_expected = json_decode($user, true);

        $response = new Response($ticket);
        $url = parse_url(self::$server->setResponseOfPath('/tickets/1', $response));
        self::$server->setResponseOfPath('/users/1', new Response($user));

        $domain = "${url['host']}:${url['port']}";
        $client = new ZendeskClient("${url['scheme']}://$domain", 'test', 'test');

        $unfurler = new ZendeskUnfurler($client, $domain, new NullLogger());
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

        $unfurl = array_values($event->getUnfurls())[0];

        $this->assertArrayHasKey('title', $unfurl);
        $this->assertArrayHasKey('text', $unfurl);
        $this->assertArrayHasKey('ts', $unfurl);
        $this->assertArrayHasKey('footer', $unfurl);

        $this->assertContains(
          $ticket_expected['ticket']['subject'],
          $unfurl['title']
        );
        $this->assertEquals(
          $ticket_expected['ticket']['description'],
          $unfurl['text']
        );
        $this->assertEquals(
          strtotime($ticket_expected['ticket']['created_at']),
          $unfurl['ts']
        );
        $this->assertContains(
          $user_expected['user']['name'],
          $unfurl['footer']
        );
    }
}
