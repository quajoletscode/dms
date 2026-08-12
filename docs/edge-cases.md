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

## Phase 2 — Warehouse Inbound (PO, GRN, Purchase Returns)

| Edge case | Behavior | Test |
|---|---|---|
| The creator of a PO approves their own PO | Blocked (`SegregationOfDutiesException`) | `tests/Feature/Warehouse/PurchaseOrderLifecycleTest.php` |
| A different user approves a submitted PO | Succeeds; `approved_by` recorded | same |
| Every illegal PO status transition (skip a step, act on a terminal state, etc.) | Blocked (`IllegalTransitionException`), enumerated as a Pest dataset | same |
| A PO is cancelled from draft/submitted/approved | Allowed | same |
| A PO has any GRN posted against it, then someone tries to cancel it | Blocked — must `ClosePurchaseOrder` instead | `tests/Feature/Warehouse/PurchaseOrderCancelledAfterPartialGrnTest.php` |
| Two partial GRNs are posted against the same PO line | `qty_received` sums exactly; PO status becomes `partially_received` then `received` | `tests/Feature/Warehouse/GrnPartialReceiptTest.php` |
| A GRN is posted twice | Blocked (`ImmutableRecordException`) — status guard, not just a UI disable | same |
| Receiving more than the PO's remaining quantity, no `grn.override` | Blocked (`OverReceiptNotAllowedException`) | `tests/Feature/Warehouse/GrnOverReceiptTest.php` |
| Same, with `grn.override` granted | Allowed, and the GRN creation is itself audited (`Auditable` on `Grn`) | same |
| Several GRNs posted against one PO line, in sequence | Accumulate to the exact expected total — the property an atomic `col = col + ?` increment guarantees under real concurrency too (see `decisions.md` ADR-20 for why literal thread-concurrency testing isn't meaningful on SQLite `:memory:`) | `tests/Feature/Warehouse/GrnConcurrentReceiptTest.php` |
| Posting a GRN | Debits Inventory and credits Accounts Payable for the exact same amount, credit line tagged to the supplier via `partner_type`/`partner_id` | `tests/Feature/Warehouse/GrnPostsBalancedJournalTest.php` |
| Multiple suppliers with posted GRNs | The AP control account's total exactly equals the sum of its per-supplier sub-ledger lines | same |
| GRN line for an expiry-tracked product with a batch number and future expiry | Batch created (or reused if the batch number already exists for that product), carried onto the stock ledger entry at posting | `tests/Feature/Warehouse/GrnBatchExpiryTest.php` |
| GRN line naming a batch number that already exists for that product | Reuses the existing batch — no duplicate, no unique-constraint error | same |
| GRN line with an expiry date already in the past | Blocked (`ExpiredBatchException`) — reuses the exact Phase 1 guard, not a re-implementation | same |
| A direct GRN (no PO) without `grn.direct.create` | Blocked (`AuthorizationException`) | `tests/Feature/Warehouse/DirectGrnTest.php` |
| A direct GRN with `grn.direct.create` | Succeeds, `po_id` is null, posts stock normally | same |
| A purchase return for more than a GRN line's received quantity, in one request or split across several returns | Blocked (`ExcessiveReturnException`) — the "already returned" running total is recomputed from `purchase_return_items`, not cached | `tests/Feature/Warehouse/PurchaseReturnTest.php` |
| A purchase return for exactly the remaining returnable quantity | Allowed | same |
| A purchase return posts | Reduces warehouse stock (`StockMover`) and reverses the GRN's accounting (Dr AP / Cr Inventory) | same |
| Phase 1's `RolePermissionSeeder` granted `grn.direct.create`/`grn.override` to every `warehouse_manager` by default | Found by the tests written to prove these are blocked without permission — fixed to be a per-user elevation, not a role default | see `decisions.md` ADR-23 |

## Phase 3 — Warehouse Outbound (Sales Order, Proforma, Invoice, Credit Notes)

| Edge case | Behavior | Test |
|---|---|---|
| Every illegal Sales Order status transition (skip a step, act on a terminal state, etc.) | Blocked (`IllegalTransitionException`), enumerated as a Pest dataset | `tests/Feature/Sales/SalesOrderLifecycleTest.php` |
| A Sales Order is cancelled from draft/confirmed/fulfilled | Allowed | same |
| Creating a Proforma Invoice | No stock movement, no journal entry — a pure quotation | `tests/Feature/Sales/ProformaNoPostingEffectTest.php` |
| Converting an expired Proforma (`valid_until` in the past) to an Invoice | Blocked (`ProformaExpiredException`) | `tests/Feature/Sales/ProformaExpiredConversionBlockedTest.php` |
| Converting a non-expired Proforma | Succeeds; Proforma flips to `converted` | same |
| Converting an already-converted Proforma a second time | Blocked (`ProformaExpiredException`, reused for "already converted") | same |
| Converting to Invoice would push the customer's live AR balance past their `credit_limit` (including `credit_limit = 0`, i.e. cash-only), without `sales.credit_override` | Blocked (`CreditLimitExceededException`) | `tests/Feature/Sales/ConvertToInvoiceCreditLimitTest.php` |
| Same, with `sales.credit_override` granted to the acting user | Allowed | same |
| `sales.credit_override` on the default `warehouse_manager` role | Not granted by default — per-user elevation only, same discipline as `grn.override` (ADR-23) | same |
| Invoicing an expiry-tracked product with stock split across batches | Nearest-expiry (unexpired) batch is issued first | `tests/Feature/Sales/FefoIssuingTest.php` |
| An expired batch has stock, but an unexpired batch also does | The expired batch is skipped entirely, even if its expiry made it the "earliest" candidate | same |
| Requested quantity exceeds all unexpired stock across every batch | Blocked (`InsufficientStockException`) | same |
| A Sales Order line splits across more than one FEFO-picked batch | The line's discount/tax is recorded once (on the first pick) — not duplicated per batch-split `InvoiceItem` row | `app/Modules/Sales/Actions/ConvertToInvoice.php` (exercised implicitly wherever FEFO splits a line; see `FefoIssuingTest.php`) |
| A product's `tax_rate` changes between Sales-Order creation and Invoice conversion | The Invoice uses the rate in effect **at conversion time**, never the rate captured on the Sales Order | `tests/Feature/Sales/TaxRateAtInvoiceDateTest.php` |
| Per-line tax rounding vs. a single document-level recomputation | The two can legitimately disagree by one pesewa; the Invoice always uses the sum of already-rounded per-line amounts, never a re-rounded document-level figure | `tests/Feature/Sales/RoundingSumsExactlyTest.php` |
| A product's unit is a derived unit (e.g. Carton, factor 12 over Piece) | Stock, cost, and price move in that one native unit throughout the whole sale pipeline; the conversion metadata stays reachable via `Product.unit` for downstream reporting | `tests/Feature/Sales/UnitConversionSaleFromCartonTest.php` |
| An Invoice payment less than the remaining balance | Status becomes `partially_paid`; further payments accepted | `tests/Feature/Sales/InvoicePartPaymentTest.php` |
| Payments summing to exactly the grand total | Status becomes `paid` | same |
| A payment exceeding the invoice's remaining balance | Posted in full (never capped) — drives the customer's AR balance negative, which *is* their credit balance (no separate credit-balance column) | `tests/Feature/Sales/InvoiceOverpaymentBecomesCreditTest.php` |
| A credit note requesting more than an invoice line's remaining creditable quantity (checked cumulatively across all credit notes against that line, not just the current request) | Blocked (`ExcessiveCreditNoteException`) | `tests/Feature/Sales/CreditNoteExceedsOriginalBlockedTest.php` |
| Crediting exactly the remaining quantity, split across multiple credit notes | Allowed | same |
| Resolving a Sales Order's/Proforma's warehouse from inside an internal Action (`ConvertToInvoice`) for a user not pivoted to that warehouse via `warehouse_user` | `Warehouse` is `WarehouseScope`-scoped, so the `belongsTo` relation silently returns `null` for such a user, previously surfacing as a fatal `TypeError` rather than a clear error — fixed by passing the already-known `warehouse_id` column through instead of resolving the relation; see `decisions.md` ADR-28 | caught running `ProformaExpiredConversionBlockedTest.php`; regression-covered by every Phase 3 test that calls `ConvertToInvoice` |

## Phase 4 — Warehouse POS

| Edge case | Behavior | Test |
|---|---|---|
| A user tries to open a second till session while one is already open | Blocked (`TillSessionAlreadyOpenException`) | `tests/Feature/Pos/TillSessionLifecycleTest.php` |
| Closing a till session with no cash movements and a matching count | `variance = 0`; no journal entry posted | same |
| Closing an already-closed till session | Blocked (`IllegalTransitionException`) — a same-state call is **not** a silent no-op here, unlike the other `*Transitions` guards; see `decisions.md` ADR-33 | same |
| Selling the last unit of a product, then immediately attempting to sell it again | Second attempt blocked (`InsufficientStockException`) — the sequential-contention proxy for "two tills racing," per the `GrnConcurrentReceiptTest` precedent (ADR-20) | `tests/Feature/Pos/PosSaleConcurrentLastUnitTest.php` |
| A POS sale paid across two different payment methods | Both `invoice_payments` rows created, both linked to the till session, invoice reaches `paid` | `tests/Feature/Pos/PosSplitPaymentTest.php` |
| A POS sale with a payment less than the total, within the customer's credit limit | Allowed; invoice `partially_paid` | same |
| A POS sale fully paid in the same transaction, for a `credit_limit = 0` (cash-only) customer | Allowed — the credit-limit check nets out same-transaction payments first, so a fully-paid sale never trips it | `tests/Feature/Pos/PosSplitPaymentTest.php`; see `decisions.md` ADR-30 |
| A line discount at or below the configured threshold | Allowed without `pos.discount_override` | `tests/Feature/Pos/PosDiscountOverrideThresholdTest.php` |
| A line discount above the threshold, without `pos.discount_override` | Blocked (`DiscountOverrideRequiredException`) | same |
| Same, with `pos.discount_override` granted | Allowed | same |
| `pos.discount_override` on the default `wholesale_cashier` role | Not granted by default — per-user elevation only | same |
| Till counted cash less than expected (shortage) | Posts Dr Cash Over/Short / Cr Cash on Hand for the shortfall | `tests/Feature/Pos/TillZReportVarianceTest.php` |
| Till counted cash more than expected (overage) | Posts Dr Cash on Hand / Cr Cash Over/Short for the excess | same |
| Cash-out movements (e.g. a bank drop) during a session | Reduce the expected float accordingly | same |
| A sale attempted against a till session that has already been closed | Blocked (`TillSessionClosedException`); no partial `Invoice`, stock movement, or journal entry is left behind — the actual backend guarantee behind "cart preserved, nothing double-posted" | `tests/Feature/Pos/SessionExpiryMidSaleTest.php`; see `decisions.md` ADR-34 |

## Phase 5 — Van / DSR Operations

| Edge case | Behavior | Test |
|---|---|---|
| The requester of a loadout also attempts to approve it | Blocked (`SegregationOfDutiesException`) | `tests/Feature/Van/LoadoutApprovalSegregationTest.php` |
| A different user approves a requested loadout | Succeeds; approved qty defaults to requested qty | same |
| Requesting a loadout for more than the warehouse currently holds | Blocked (`InsufficientStockException`) | `tests/Feature/Van/LoadoutExceedsWarehouseStockTest.php` |
| Editing an approved qty upward beyond available warehouse stock | Blocked at approval, even if the original request was valid | same |
| Requesting a loadout/loadin for a batch/expiry-tracked product | Blocked (`BatchTrackedLoadoutUnsupportedException`) — batch-tracked van loadouts are a deferred enhancement, not silently mishandled | `decisions.md` ADR-35 |
| A loadout marked `loaded` but never confirmed received | Appears in `LoadoutRequest::stuckInTransit()` | `tests/Feature/Van/LoadoutStuckInTransitReportTest.php` |
| A loadout still in `requested`/`approved` (not yet loaded) | Does not appear in the stuck-in-transit report | same |
| `MarkLoadoutLoaded` | Status-only checkpoint — no stock ledger entry (LO-03) | `tests/Feature/Van/LoadoutPartialConfirmationDiscrepancyTest.php` |
| `ConfirmLoadoutReceipt` with received qty less than loaded qty | Warehouse loses the full loaded qty, van gains only the received qty; the shortfall is recorded via `LoadoutRequestItem::discrepancy()`, not silently absorbed into either balance | same |
| `ConfirmLoadoutReceipt` with received qty equal to loaded qty | `discrepancy()` is zero | same |
| Accepting a loadin with both good and damaged quantities on one line | Good qty returns to the warehouse; damaged qty moves to the per-warehouse `'damages'` stock location and is written off (Dr Inventory Loss / Cr Inventory) | `tests/Feature/Van/LoadinGoodVsDamagedSplitTest.php` |
| Accepting a loadin with zero damaged quantity | No write-off journal posted (`PostingEngine` rejects a zero-value line; `AcceptLoadin` skips the call entirely) | same |
| Generating a DSR settlement for a van/day with real loadout + van sale + loadin activity | `opening + loadout − sales − loadin = closing` holds exactly, computed from `stock_ledger` sums scoped by `doc_type` and date range | `tests/Feature/Van/DsrSettlementIdentityInvariantTest.php` |
| Generating a settlement twice for the same van/day | Updates the existing row (unique-constrained on `van_storage_id` + `settlement_date`) rather than erroring or duplicating | same; bug found and fixed — see `decisions.md` ADR-41 |
| Signing off a settlement with counted cash equal to expected | `variance = 0`, status `signed_off` | `tests/Feature/Van/CashShortOverVarianceSignoffTest.php` |
| Signing off with less cash than expected (shortage) | Negative variance recorded | same |
| Signing off with more cash than expected (overage) | Positive variance recorded | same |
| Generating a settlement without a cash count | Status stays `generated`; a later call with a count signs it off in place | same |
| A DSR field sale (`RecordVanSale`) fully paid in the same transaction, for a `credit_limit = 0` customer | Allowed — same same-transaction-payment netting as POS (`decisions.md` ADR-30/ADR-40) | exercised throughout `DsrSettlementIdentityInvariantTest.php`/`CashShortOverVarianceSignoffTest.php` |

## Phase 6 — Financial Management

| Edge case | Behavior | Test |
|---|---|---|
| A bank deposit/withdrawal/transfer with a duplicate slip reference | Blocked (`DuplicateSlipReferenceException`) | `tests/Feature/Finance/DuplicateDepositSlipReferenceBlockedTest.php` |
| Two deposits with no slip reference at all | Both allowed — a nullable unique column permits any number of `NULL`s | same |
| A bank transfer with a charge | Posts Dr destination account + Dr Bank Charges / Cr source account (the full amount + charge that left the source), one journal | `tests/Feature/Finance/BankDepositWithdrawalTransferTest.php` |
| A bank transfer with no charge | Posts only two lines (no zero-value Bank Charges line) | same |
| Reconciling a list of bank transactions | Marked `reconciled` | same |
| An expense paid with no bank account specified | Posted against Cash on Hand | `tests/Feature/Finance/RecordExpenseTest.php` |
| An expense paid from a named bank account | Posted against that bank account's own GL account, not Cash on Hand | same |
| A manual journal whose debits ≠ credits | Rejected (`UnbalancedJournalException`) before anything is persisted — no `ManualJournal` row, no journal entry | `tests/Feature/Finance/UnbalancedManualJournalRejectedTest.php` |
| A balanced manual journal at or below the approval threshold | Posts immediately, no approval step | same |
| A balanced manual journal above the approval threshold | Held `pending_approval`; no `journal_entries` row until approved | `tests/Feature/Finance/JournalApprovalThresholdSegregationTest.php` |
| The requester of a pending manual journal also attempts to approve it | Blocked (`SegregationOfDutiesException`) | same |
| A different user approves a pending manual journal | Posts it | same |
| Posting on a date inside a closed fiscal period | Blocked (`PostingIntoClosedPeriodException`), checked before any DB write | `tests/Feature/Finance/PostingIntoClosedPeriodRejectedTest.php` |
| Posting on a date with no fiscal period configured at all | Unrestricted — periods are opt-in, not mandatory (`decisions.md` ADR-42) | same |
| Closing an already-closed fiscal period | Blocked (`IllegalTransitionException`) | same |
| Reversing a posted journal | Creates a new posted journal with every line mirrored (debit ↔ credit); the original flips to `reversed` | `tests/Feature/Finance/ReversalOfReversalTest.php` |
| Reversing a reversal | Reproduces the original journal's exact lines (double negation); allowed, since the *reversal* entry is still `posted` | same |
| Reversing the same journal a second time | Blocked (`IllegalTransitionException`) — a `reversed` entry has no further allowed transition | same |
| A DSR collection, handover, and deposit chain | Every hop traceable to a real person: `dsr_user_id` (collected by), `handed_over_by`, the depositing `bank_transaction`'s `created_by` | `tests/Feature/Finance/DsrCashChainTraceabilityTest.php` |
| Several suppliers with posted GRNs / several customers with posted invoices | The AP/AR control account totals exactly equal the sum of their per-partner sub-ledger lines | `tests/Feature/Finance/ArApControlReconciliationInvariantTest.php` |
| Several manual journals posted across different accounts | Trial balance's total debits always equal total credits | `tests/Feature/Finance/TrialBalanceBalancesInvariantTest.php` |
| SQLite's `UNIQUE constraint failed` message never names the violated index (unlike MySQL) | `RecordBankDeposit`/`Withdrawal`/`Transfer`, `StockMover`, and `DocumentNumberGenerator` all check for both the MySQL-style named-index string and the SQLite-style bare `table.column` string | `decisions.md` ADR-48; `tests/Feature/Finance/DuplicateDepositSlipReferenceBlockedTest.php` |

Business-workflow edge cases still pending: DSR mobile offline sync idempotency, the remaining 9 planned report types (ADR-47), etc. — begin with Phase 7 onward, see `requirements-matrix.md` and the build plan's per-phase test lists.
