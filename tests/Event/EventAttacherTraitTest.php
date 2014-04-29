<?php

namespace GuzzleHttp\Tests\Event;

use GuzzleHttp\Event\HasEmitterInterface;
use GuzzleHttp\Event\HasEmitterTrait;
use GuzzleHttp\Event\EventAttacherTrait;

class ObjectWithEvents implements HasEmitterInterface
{
    use HasEmitterTrait, EventAttacherTrait;

    public function __construct(array $args = [])
    {
        $this->prepareEvents($args, ['foo', 'bar']);
        $this->attachListeners($this);
    }
}

class EventAttacherTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistersEvents()
    {
        $fn = function() {};
        $o = new ObjectWithEvents([
            'foo' => $fn,
            'bar' => $fn,
        ]);

        $this->assertEquals([
            ['name' => 'foo', 'fn' => $fn, 'priority' => 0, 'once' => false],
            ['name' => 'bar', 'fn' => $fn, 'priority' => 0, 'once' => false],
        ], $this->readAttribute($o, 'eventListeners'));

        $this->assertCount(1, $o->getEmitter()->listeners('foo'));
        $this->assertCount(1, $o->getEmitter()->listeners('bar'));
    }

    public function testRegistersEventsWithPriorities()
    {
        $fn = function() {};
        $o = new ObjectWithEvents([
            'foo' => ['fn' => $fn, 'priority' => 99, 'once' => true],
            'bar' => ['fn' => $fn, 'priority' => 50],
        ]);

        $this->assertEquals([
            ['name' => 'foo', 'fn' => $fn, 'priority' => 99, 'once' => true],
            ['name' => 'bar', 'fn' => $fn, 'priority' => 50, 'once' => false],
        ], $this->readAttribute($o, 'eventListeners'));
    }

    public function testRegistersMultipleEvents()
    {
        $fn = function() {};
        $eventArray = [['fn' => $fn], ['fn' => $fn]];
        $o = new ObjectWithEvents([
            'foo' => $eventArray,
            'bar' => $eventArray,
        ]);

        $this->assertEquals([
            ['name' => 'foo', 'fn' => $fn, 'priority' => 0, 'once' => false],
            ['name' => 'foo', 'fn' => $fn, 'priority' => 0, 'once' => false],
            ['name' => 'bar', 'fn' => $fn, 'priority' => 0, 'once' => false],
            ['name' => 'bar', 'fn' => $fn, 'priority' => 0, 'once' => false],
        ], $this->readAttribute($o, 'eventListeners'));

        $this->assertCount(2, $o->getEmitter()->listeners('foo'));
        $this->assertCount(2, $o->getEmitter()->listeners('bar'));
    }

    public function testRegistersEventsWithOnce()
    {
        $called = 0;
        $fn = function () use (&$called) { $called++; };
        $o = new ObjectWithEvents(['foo' => ['fn' => $fn, 'once' => true]]);
        $ev = $this->getMock('GuzzleHttp\Event\EventInterface');
        $o->getEmitter()->emit('foo', $ev);
        $o->getEmitter()->emit('foo', $ev);
        $this->assertEquals(1, $called);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesEvents()
    {
        $o = new ObjectWithEvents(['foo' => 'bar']);
    }
}
