<?php

namespace App\Modules\Finance\Domain;

use App\Modules\Finance\Domain\Contracts\PostingRule;
use InvalidArgumentException;

/**
 * Maps a business event type (e.g. "grn.posted") to the PostingRule that
 * knows how to turn that document into balanced journal lines. Populated by
 * FinanceServiceProvider and by each module's own service provider as
 * documents that post are introduced.
 */
final class PostingRuleRegistry
{
    /** @var array<string, PostingRule> */
    private array $rules = [];

    public function register(string $eventType, PostingRule $rule): void
    {
        $this->rules[$eventType] = $rule;
    }

    public function for(string $eventType): PostingRule
    {
        return $this->rules[$eventType]
            ?? throw new InvalidArgumentException("No posting rule registered for event [{$eventType}].");
    }
}
