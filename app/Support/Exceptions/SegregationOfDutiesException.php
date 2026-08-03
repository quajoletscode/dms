<?php

namespace App\Support\Exceptions;

use RuntimeException;

/**
 * The user who created a document cannot also approve it — reused across
 * Purchase Orders, Loadouts, and journal approvals.
 */
final class SegregationOfDutiesException extends RuntimeException {}
