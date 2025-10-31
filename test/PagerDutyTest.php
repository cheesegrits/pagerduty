<?php

/**
 * cd into root
 * ./vendor/bin/phpunit test
 */

use PagerDuty\AcknowledgeEvent;
use PagerDuty\ResolveEvent;
use PagerDuty\TriggerEvent;
use PHPUnit\Framework\TestCase;

class PagerDutyTest extends TestCase
{
    public function testAckEvent()
    {
        $routingKey = 'sv123';
        $dedupKey = 'inc123';

        $event = new AcknowledgeEvent($routingKey, $dedupKey);

        $expect = ['routing_key' => $routingKey, 'dedup_key' => $dedupKey, 'event_action' => 'acknowledge'];
        $this->assertEquals($expect, $event->toArray());
    }

    public function testResolveEvent()
    {
        $routingKey = 'sv123';
        $dedupKey = 'inc123';

        $event = new ResolveEvent($routingKey, $dedupKey);

        $expect = ['routing_key' => $routingKey, 'dedup_key' => $dedupKey, 'event_action' => 'resolve'];
        $this->assertEquals($expect, $event->toArray());
    }

    public function testTriggerEvent()
    {
        $routingKey = 'sv123';

        $event = new TriggerEvent($routingKey, 'FAILURE for production/HTTP on machine srv01.acme.com', 'localhost', TriggerEvent::ERROR);
        $event
            ->setPayloadClass('ping failure')
            ->setPayloadComponent('web server')
            ->setPayloadTimestamp('2018-05-01T08:42:58.315+0000')
            ->setPayloadCustomDetails(['ping_time' => '1500ms', 'load_avg' => 0.75])
            ->addLink('http://acme.pagerduty.com')
            ->addLink('http://acme.pagerduty.com', 'View the incident on PagerDuty')
            ->addImage('https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,1')
            ->addImage('https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,2', 'http://acme.pagerduty.com/href')
            ->addImage('https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,3', 'http://acme.pagerduty.com/href', 'Zig Zag')
        ;

        $this->assertArrayNotHasKey('dedup_key', $event->toArray());

        $expect = [
            'routing_key' => $routingKey,
            'event_action' => 'trigger',
            'payload' => [
                'summary' => 'FAILURE for production/HTTP on machine srv01.acme.com',
                'source' => 'localhost',
                'severity' => 'error',
                'class' => 'ping failure',
                'component' => 'web server',
                'timestamp' => '2018-05-01T08:42:58.315+0000',
                'custom_details' => [
                    'ping_time' => '1500ms',
                    'load_avg' => 0.75,
                ],
            ],
            'links' => [
                ['href' => 'http://acme.pagerduty.com'],
                ['href' => 'http://acme.pagerduty.com', 'text' => 'View the incident on PagerDuty']
            ],
            'images' => [
                ['src' => 'https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,1'],
                ['src' => 'https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,2', 'href' => 'http://acme.pagerduty.com/href'],
                ['src' => 'https://chart.googleapis.com/chart?chs=600x400&chd=t:6,2,9,5,2,5,7,4,8,2,1&cht=lc&chds=a&chxt=y&chm=D,0033FF,0,0,5,3', 'href' => 'http://acme.pagerduty.com/href', 'alt' => 'Zig Zag'],
            ]
        ];

        $this->assertEquals($expect, $event->toArray());
    }

    public function testTriggerHashEvent()
    {
        $routingKey = 'sv123';

        $msg = 'FAILURE for production/HTTP on machine srv01.acme.com';
        $event = new TriggerEvent($routingKey, $msg, 'localhost', TriggerEvent::ERROR, true);

        $expect = ['dedup_key' => 'md5-' . md5($msg)];
        $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys($expect, $event->toArray(), array_keys($expect));
    }

    public function testTypeError()
    {
        $this->expectException(TypeError::class);
        $event = new TriggerEvent('sv123', 'Blah', 'foo', TriggerEvent::ERROR, true);
        /** @noinspection PhpParamsInspection */
        $event->setPayloadCustomDetails(null);
    }
}
