<?php

namespace PagerDuty\Exceptions;

use Exception;

/**
 * PagerDutyException
 *
 * @author adil
 */
class PagerDutyException extends Exception
{

    protected array $errors;

    public function __construct($message, array $errors)
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * 
     * @return array
     * @noinspection PhpUnused
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
