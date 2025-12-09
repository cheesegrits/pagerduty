<?php

namespace PagerDuty;

/**
 * An 'acknowledge' Event
 *
 * @author adil
 */
class AcknowledgeEvent extends Event
{

    public function __construct(string $routingKey, string $dedupKey)
    {
        parent::__construct($routingKey, 'acknowledge');

        $this->setDeDupKey($dedupKey);
    }
}
