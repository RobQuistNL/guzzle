<?php

namespace GuzzleHttp\Event;

/**
 * Trait that provides methods for extract event listeners specified in an array
 * and attaching them to an emitter owned by the object or one of its direct
 * dependencies.
 */
trait EventAttacherTrait
{
    /** @var array of hashes containing 'name', 'fn', 'priority', and 'once' */
    private $eventListeners;

    /**
     * Attaches the stored event listeners and properly sets their priorities
     * and whether or not they are are only executed once.
     *
     * @param HasEmitterInterface $object Object that has the event emitter.
     */
    private function attachListeners(HasEmitterInterface $object)
    {
        if ($this->eventListeners) {
            $emitter = $object->getEmitter();
            foreach ($this->eventListeners as $ev) {
                if ($ev['once']) {
                    $emitter->once($ev['name'], $ev['fn'], $ev['priority']);
                } else {
                    $emitter->on($ev['name'], $ev['fn'], $ev['priority']);
                }
            }
        }
    }

    /**
     * Extracts the allowed events from the provided array, and ignores anything
     * else in the array. The event listener must be specified as a callable or
     * as an array of event listener data ("name", "fn", "priority", "once").
     *
     * @param array $events        Array containing callables or sub-arrays of
     *                             data to be prepared as event listeners.
     * @param array $allowedEvents Names of events to look for in the provided
     *                             $events array. Other keys are ignored.
     */
    private function prepareEvents(array $events, array $allowedEvents)
    {
        foreach ($allowedEvents as $name) {
            if (isset($events[$name])) {
                if (is_callable($events[$name])) {
                    $this->eventListeners[] = [
                        'name'     => $name,
                        'fn'       => $events[$name],
                        'priority' => 0,
                        'once'     => false
                    ];
                } else {
                    $this->prepareEvent($name, $events[$name]);
                }
            }
        }
    }

    /**
     * Creates a complete event listener definition from the provided array of
     * listener data. Also works recursively if more than one listeners are
     * contained in the provided array.
     *
     * @param string $eventName Name of the event the listener is for.
     * @param array  $event     Event listener data to prepare.
     *
     * @throws \InvalidArgumentException if the event data is malformed.
     */
    private function prepareEvent($eventName, $event)
    {
        static $default = ['priority' => 0, 'once' => false];

        if (!is_array($event)) {
            throw new \InvalidArgumentException('Each event listener must be a '
                . 'callable or an array of associative arrays where each '
                . 'associative array contains a "fn" key.');
        }

        if (isset($event['fn'])) {
            $event['name'] = $eventName;
            $this->eventListeners[] = $event + $default;
        } else {
            foreach ($event as $e) {
                $this->prepareEvent($eventName, $e);
            }
        }
    }
}
