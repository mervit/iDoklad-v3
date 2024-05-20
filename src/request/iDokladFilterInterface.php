<?php

/**
 * Interface that represents filter entities
 *
 * @author mervit Vítězslav Mergl
 */

namespace mervit\iDoklad\request;

interface iDokladFilterInterface {
    public function buildQuery(): string;
}