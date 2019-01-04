<?php

namespace ZendeskSlackUnfurl\Event\Subscriber;

use Psr\Log\LoggerInterface;
use SlackUnfurl\Event\Events;
use SlackUnfurl\Event\UnfurlEvent;
use SlackUnfurl\LoggerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ZendeskSlackUnfurl\ZendeskClient;

class ZendeskUnfurler implements EventSubscriberInterface
{
    use LoggerTrait;

    /** @var ZendeskClient */
    private $client;
    /** @var string */
    private $domain;

    public function __construct(
        ZendeskClient $client,
        string $domain,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->domain = $domain;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::SLACK_UNFURL => ['unfurl', 10],
        ];
    }

    public function unfurl(UnfurlEvent $event)
    {
        foreach ($event->getMatchingLinks($this->domain) as $link) {
            $unfurl = $this->getTicketUnfurl($link['url']);
            if ($unfurl) {
                $event->addUnfurl($link['url'], $unfurl);
            }
        }
    }

    /**
     * @param string $url
     * @return array|null
     */
    private function getTicketUnfurl(string $url)
    {
        $ticket = $this->getTicketDetails($url);
        $this->debug('zendesk', ['ticket' => $ticket]);

        if (!$ticket) {
            return null;
        }

        return [
            'title' => "<$url|#{$ticket['id']}>: {$ticket['subject']}",
            'text' => $ticket['description'],
            'ts' => (new \DateTime($ticket['created_at']))->getTimestamp(),
        ];
    }

    /**
     * @param string $url
     * @return array|null
     */
    private function getTicketDetails(string $url)
    {
        if (!preg_match("#^https?://\Q{$this->domain}\E/agent/tickets/(?P<id>\d+)#", $url, $m)) {
            return null;
        }

        return $this->client->getTicket($m['id']);
    }
}
