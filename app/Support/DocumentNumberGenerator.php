<?php

namespace App\Support;

use App\Models\DocumentNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Allocates gapless, sequential document numbers per (scope, document type,
 * fiscal year) using a locked counter row — never MAX(id)+1.
 */
final class DocumentNumberGenerator
{
    private const MAX_ATTEMPTS = 3;

    public function next(string $documentType, string $scopeType, int $scopeId = 0, ?int $fiscalYear = null): string
    {
        $fiscalYear ??= (int) now()->format('Y');

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->allocate($documentType, $scopeType, $scopeId, $fiscalYear);
            } catch (QueryException $e) {
                if ($attempt === self::MAX_ATTEMPTS || ! $this->isSequenceUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Unable to allocate a document number after retries.');
    }

    private function allocate(string $documentType, string $scopeType, int $scopeId, int $fiscalYear): string
    {
        return DB::transaction(function () use ($documentType, $scopeType, $scopeId, $fiscalYear) {
            $sequence = DocumentNumberSequence::query()
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId)
                ->where('document_type', $documentType)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = DocumentNumberSequence::query()->create([
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'document_type' => $documentType,
                    'fiscal_year' => $fiscalYear,
                    'next_number' => 1,
                ]);
            }

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return $this->format($documentType, $scopeType, $scopeId, $fiscalYear, $number);
        });
    }

    private function isSequenceUniqueViolation(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'doc_number_scope_unique');
    }

    /**
     * scope_id is embedded so two different warehouses never print the same
     * document number (0 = company-wide scope, omitted from the number).
     */
    private function format(string $documentType, string $scopeType, int $scopeId, int $fiscalYear, int $number): string
    {
        $scopeSegment = $scopeId > 0 ? sprintf('-%s%d', strtoupper(substr($scopeType, 0, 1)), $scopeId) : '';

        return sprintf('%s%s-%d-%06d', strtoupper($documentType), $scopeSegment, $fiscalYear, $number);
    }
}
