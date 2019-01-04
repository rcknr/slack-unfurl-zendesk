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
        $json_string = file_get_contents('./Resources/ticket.json');
        $expected = json_decode($json_string, true);

        $response = new Response($json_string);
        $url = parse_url(self::$server->setResponseOfPath('/api/v2/tickets/1', $response));

        $domain = "${url['host']}:${url['port']}";
        $client = new ZendeskClient($domain, 'test', 'test', $url['scheme']);

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

        $this->assertEquals($expected['ticket']['description'], $unfurl['text']);
    }
}
