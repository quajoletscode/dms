<?php

namespace App\Modules\Sales\Domain\Exceptions;

use RuntimeException;

final class TillSessionAlreadyOpenException extends RuntimeException {}
