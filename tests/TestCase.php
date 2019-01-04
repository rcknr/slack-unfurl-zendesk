<?php

namespace ZendeskSlackUnfurl\Test;

use donatj\MockWebServer\MockWebServer;

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
}
