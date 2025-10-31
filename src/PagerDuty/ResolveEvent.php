<?php

namespace PagerDuty;

/**
 * A 'resolve' Event
 *
 * @author adil
 */
class ResolveEvent extends Event
{

    public function __construct(string $routingKey, string $dedupKey)
    {
        parent::__construct($routingKey, 'resolve');

        $this->setDeDupKey($dedupKey);
    }
}
