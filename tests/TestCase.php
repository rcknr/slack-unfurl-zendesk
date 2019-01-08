<?php

namespace ZendeskSlackUnfurl\Test;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var MockWebServer */
    protected static $server;

    public static function setUpBeforeClass()
    {
        self::$server = new MockWebServer;
        self::$server->start();
    }

    static function tearDownAfterClass()
    {
        self::$server->stop();
    }

    static function setUpResource($name)
    {
        $file = file_get_contents(__DIR__ . "/Resources/$name.json");
        $json = json_decode($file, true);
        $resource = reset($json);
        $path = sprintf('/%s/%s', $name, $resource['id']);
        self::$server->setResponseOfPath($path, new Response($file));

        return $resource;
    }
}
