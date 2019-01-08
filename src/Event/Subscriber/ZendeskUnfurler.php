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
    /** @var array */
    private $fields;

    public function __construct(
        ZendeskClient $client,
        array $config,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;

        $this->domain = $config['domain'];
        $this->fields = array_filter(
            array_map('intval', explode(',', $config['fields']))
        );
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
            'ts' => strtotime($ticket['created_at']),
            'footer' => $this->formatFooter($ticket),
            'fields' => $this->formatFields($ticket),
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

        /** @noinspection PhpUndefinedMethodInspection */
        return $this->client->getTicket($m['id']);
    }

    private function formatFooter(array $ticket)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $submitter = $this->client->getUser($ticket['submitter_id']);

        if ($submitter) {
            return sprintf(
                'Submitted by <https://%s/agent/#/users/%s|%s>',
                $this->domain,
                $ticket['submitter_id'],
                $submitter['name']);
        }

        return null;
    }

    private function formatFields(array $ticket)
    {
        $fields = array_filter($ticket['fields'], function($field) {
            return in_array($field['id'], $this->fields) && $field['value'];
        });

        if($fields) {
            /** @noinspection PhpUndefinedMethodInspection */
            $ticket_fields = array_reduce($this->client->getTicketFields(), function ($carry, $ticket_field) {
                if ($ticket_field['type'] === 'tagger') {
                    $carry[$ticket_field['id']] = [
                        'title' => $ticket_field['title'],
                        'values' => array_combine(
                            array_column($ticket_field['custom_field_options'], 'value'),
                            array_column($ticket_field['custom_field_options'], 'name')
                        )
                    ];
                }

                return $carry;
            }, []);

            return array_reduce($fields, function ($carry, $field) use ($ticket_fields) {
                if (array_key_exists($field['id'], $ticket_fields)) {
                    $carry[] = [
                        'title' => $ticket_fields[$field['id']]['title'],
                        'value' => $ticket_fields[$field['id']]['values'][$field['value']],
                        'short' => true,
                    ];
                }

                return $carry;
            }, []);
        }

        return null;
    }
}
