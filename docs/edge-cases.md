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

## Phase 1 — Master Data

| Edge case | Behavior | Test |
|---|---|---|
| Warehouse Manager queries warehouses, including a direct-ID lookup of one they're not assigned to | Excluded from listing; direct `find()` returns null (404 at the HTTP layer) — `WarehouseScope` filters at the query level, not just the UI | `tests/Feature/MasterData/WarehouseManagementTest.php` |
| Super Admin queries warehouses | Sees all, regardless of `warehouse_user` assignment | same |
| DSR queries vans, including a direct-ID lookup of another DSR's van | Excluded / null — `VanScope` | `tests/Feature/MasterData/RolePermissionScopingTest.php` |
| Registering a van with a DSR who already drives another van | Blocked (`van_storages.dsr_user_id` unique constraint) — "one DSR per van at a time" | `tests/Feature/Van/VanRegistrationTest.php` |
| Reassigning a van's DSR while it has stock on board, no handover note given | Blocked (`VanHandoverRequiredException`); prior DSR stays assigned | `tests/Feature/Van/VanReassignmentTest.php` |
| Reassigning a van's DSR while it has stock on board, with a handover note | Succeeds; prior assignment's `van_dsr_history` row gets `unassigned_at` + the note, a new row opens for the incoming DSR | same |
| Reassigning a van's DSR with no stock on board | Succeeds without requiring a handover note | same |
| Carton→piece unit conversion math | `Quantity::multiply()` against the unit's `conversion_factor`, exact at 3 decimal places | `tests/Feature/MasterData/ProductUnitConversionTest.php` |
| Changing a unit's conversion factor after stock already exists under it | Historical `stock_ledger.unit_cost` rows are untouched (immutability holds across master-data edits, not just direct ledger writes) | same |
| Batch with an expiry date in the past, on receipt | Blocked (`ExpiredBatchException`) unless `allow_expired` is explicitly passed | `tests/Feature/MasterData/BatchExpiryTest.php` |
| Switching a product from non-expiry-tracked to tracked after stock already exists | Existing `stock_ledger`/`batch_id` rows are untouched; the flag only affects future receipts | same |
| Eloquent `create()` on a model with a DB-level boolean default (`is_active`) | The in-memory returned model does *not* reflect the DB default unless the Action sets it explicitly — see `decisions.md` ADR-16 | `tests/Feature/MasterData/WarehouseManagementTest.php` (`'a warehouse can be registered'`) |
| Adding a foreign key to `stock_ledger` on SQLite via a later migration | Silently drops any triggers attached to that table (SQLite rebuilds the table to add the constraint) — see `decisions.md` ADR-15 | `tests/Feature/Foundation/StockLedgerImmutabilityTest.php` (re-verified green after the fix) |
| Unauthenticated request to any protected route | Redirects to `/login` | `tests/Feature/Auth/LoginTest.php` |
| Login with a wrong password | Rejected with a validation error on `email`, user stays a guest | same |

Business-workflow edge cases (over-receipt, FEFO issuing, credit limits, van settlement variance, offline sync idempotency, etc.) begin with Phase 2 — see `requirements-matrix.md` and the build plan's per-phase test lists.
