# Edge Cases

Living document, appended each phase. Phase 0 has no real business documents yet, so its edge cases are invariant-level (the guarantees every later phase builds on), not workflow-level.

## Phase 0 — Foundation

| Edge case | Behavior | Test |
|---|---|---|
| Update a stock ledger row via Eloquent | Blocked, throws `ImmutableRecordException` | `tests/Feature/Foundation/StockLedgerImmutabilityTest.php` |
| Delete a stock ledger row via Eloquent | Blocked, throws `ImmutableRecordException` | same |
| Update/delete a stock ledger row bypassing Eloquent (raw query builder) | Blocked at the DB trigger layer, throws `QueryException` | same |
| Update/delete a journal line, Eloquent and raw-query layers | Same as above, mirrored for `journal_lines` | `tests/Feature/Foundation/JournalImmutabilityTest.php` |
| A posting rule resolves unbalanced lines (Σdebit ≠ Σcredit) | Rejected *before* any DB write — no partial journal, no orphaned header | `tests/Feature/Foundation/PostingEngineBalancesTest.php` |
| A posting rule resolves zero lines | Rejected (`UnbalancedJournalException`) | same |
| Posting with an unregistered event type | Rejected (`InvalidArgumentException`) from `PostingRuleRegistry::for()` | same |
| A single journal line is both a debit and a credit | Rejected at construction (`JournalLineData`) | `tests/Unit/Finance/JournalLineDataTest.php` |
| A single journal line has zero debit and zero credit | Rejected at construction | same |
| Two different warehouses' first document of the year | Each gets an independent, correctly-scoped sequence *and* a visually distinct printed number (scope id embedded — see `decisions.md` ADR-5 follow-up) | `tests/Feature/Foundation/DocumentNumberGeneratorTest.php` |
| Same document type, different fiscal years, same scope | Independent sequences, both starting at 1 | same |
| Company-wide sequence (scope id 0) | Omits the scope segment from the printed number | same |
| Updating an audited model with no actual field changes | No audit row written (empty diff is a no-op, not noise) | `tests/Feature/Foundation/AuditLogTest.php` (update case exercises a real change; the no-op path is a direct read of `Auditable::bootAuditable()`) |
| Updating an audited model | Audit `before`/`after` capture only the changed fields, not the whole row | `tests/Feature/Foundation/AuditLogTest.php` |
| `Money` arithmetic across different currencies | Rejected (`InvalidArgumentException`) — single-currency v1, mixing is always a bug | `tests/Unit/Support/MoneyTest.php` |
| `Quantity::fromString` given non-numeric input | Rejected (`InvalidArgumentException`) | `tests/Unit/Support/QuantityTest.php` |

Business-workflow edge cases (over-receipt, FEFO issuing, credit limits, van settlement variance, offline sync idempotency, etc.) begin with Phase 2 — see `requirements-matrix.md` and the build plan's per-phase test lists.
