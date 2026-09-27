<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violation d'une règle métier : message destiné à l'utilisateur final.
 */
class BusinessRuleException extends RuntimeException {}
