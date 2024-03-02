<?php

/**
 * iDoklad exception class for better error catching
 *
 * @author Jan mervit
 */

namespace mervit\iDoklad;
use Exception;

class iDokladException extends Exception {
    private $payload;

    public function __construct($message = "", $code = 0, $payload = null) {
        $this->payload = $payload;
        parent::__construct($message, $code);
    }
    
    public function getPayload() {
        return $this->payload;
    }
}
