# Architecture Decision Record

Living document. New decisions are appended as later phases raise them — never edit history, add a new entry (superseding an earlier one explicitly if needed).

---

## ADR-1 — Module structure

**Context.** The spec calls for a modular monolith (Warehouse, DSR/Van, Finance) sharing one database.
**Decision.** `app/Modules/{Identity,MasterData,Warehouse,Van,Finance}/`, each with `Domain/`, `Actions/`, `Http/{Controllers,Requests}/`, `Models/`, `Events/`, `Policies/`, `Tests/`. PSR-4: `App\Modules\ → app/Modules/`. Cross-module calls only through a module's public Actions/events.
**Consequence.** `app/Models/User.php` stays where Laravel/Breeze scaffolding expects it — not moved into Identity — to avoid breaking auth scaffolding for no benefit; Identity module holds role/permission domain logic and scoping instead.

## ADR-2 — RBAC + location scoping

**Decision.** `spatie/laravel-permission` for permission/role primitives. Location scoping (warehouse manager → assigned warehouses, DSR → own van) is separate: a `warehouse_user` pivot + `van_storages.dsr_user_id`, enforced via Eloquent global scopes, not spatie. A request needs both a permission check and a scope check.
**Consequence.** `warehouse_user` was moved from Phase 0 to Phase 1 (see ADR-15) since it needs the `warehouses` table.

## ADR-3 — Immutability enforcement (stock_ledger, journal_lines)

**Decision.** Two layers: (a) Eloquent `booted()` aborts `updating`/`deleting` with `ImmutableRecordException` (the `AppendOnly` trait); (b) DB triggers (`SIGNAL SQLSTATE '45000'` on MySQL, `RAISE(ABORT, ...)` on SQLite) block the same at the database level, so even a raw query bypassing Eloquent is stopped.
**Verified by.** `tests/Feature/Foundation/StockLedgerImmutabilityTest.php`, `JournalImmutabilityTest.php` — both layers, both tables.

## ADR-4 — Money & Quantity value objects

**Decision.** Custom `App\Support\Money` (integer minor units + currency) and `App\Support\Quantity` (BCMath decimal string, scale 3) instead of a package — full control over rounding/serialization for a single-currency (GHS) v1.
**Follow-up (implementation).** `Quantity`'s constructor takes a `numeric-string` (PHPDoc-typed, validated via `is_numeric()` in a `normalize()` helper) so PHPStan level 8 can verify every `bcadd`/`bcsub`/`bcmul`/`bccomp` call site without suppression.

## ADR-5 — Document numbering

**Decision.** `document_number_sequences` (`scope_type`, `scope_id`, `document_type`, `fiscal_year`, `next_number`), unique on the 4-tuple. `DocumentNumberGenerator::next()` locks the row (`lockForUpdate()`) inside a transaction; on a unique-constraint race (two concurrent first-allocations for the same scope) it retries up to 3 times instead of surfacing the DB error.
**Follow-up (implementation).** The formatted number embeds the scope id (e.g. `PO-W1-2026-000001` vs `PO-W2-2026-000001`) — without this, two different warehouses' first PO of the year would both print as `PO-2026-000001`, which is correct internally (separate counter rows) but confusing on a real document. `scope_id = 0` means company-wide and is omitted from the printed number.

## ADR-6 — PostingEngine

**Decision.** `PostingEngine::post(string $eventType, Model $document)` looks up a `PostingRule` from `PostingRuleRegistry` by event type, calls `resolveLines()`, asserts `Σdebit == Σcredit` (`UnbalancedJournalException` if not — checked *before* opening a transaction, so an unbalanced rule never touches the DB), then persists the journal header + lines + document link in one transaction.
**Verified by.** `tests/Feature/Foundation/PostingEngineBalancesTest.php`.

## ADR-7 — Stock ledger + balances

**Decision.** `stock_ledger` append-only; `stock_balances` is a projection (unique on location+product+batch), updated in the same Action/transaction that writes the ledger row, plus `php artisan stock:rebuild-balances` for drift correction.
**Follow-up (implementation, sequencing).** `stock_ledger.product_id`/`batch_id` and `stock_balances.product_id`/`batch_id` are plain indexed `unsignedBigInteger` columns in Phase 0 — **no FK constraint yet**, since `products`/`batches` don't exist until Phase 1 (see ADR-16).
**Follow-up (implementation, uniqueness).** `stock_balances.batch_id` defaults to `0` (not nullable) for non-batch-tracked products. A nullable column would break the uniqueness guarantee: MySQL/SQLite unique indexes treat multiple `NULL`s as distinct, so two rows for the same non-batch product at the same location could both exist. `document_number_sequences.scope_id` uses the same `0 = no specific scope` convention for the same reason.

## ADR-8 — State machines

**Decision.** No state-machine package. Each stateful document gets a native PHP backed `enum` status column plus a small `*Transitions` guard class with an explicit allow-list; illegal transitions throw `IllegalTransitionException`. Applies starting Phase 2 (PurchaseOrder is the first).

## ADR-9 — Idempotency

**Decision.** `client_uuid` nullable-unique added from Phase 0 onward to any table an offline DSR write could eventually target (`stock_ledger` already has it; `invoices`, `loadout_requests`, `loadin_requests`, `collections` get it when those tables are created in their phases).

## ADR-10 — Audit trail

**Decision.** `audit_logs` + `Auditable` trait hooked to Eloquent `created`/`updated`/`deleted`. On update, only the changed keys are recorded (before = original values of just the changed keys, after = `getChanges()` minus `updated_at`) rather than the full row, so diffs stay reviewable.
**Verified by.** `tests/Feature/Foundation/AuditLogTest.php`, exercised against `ChartOfAccount` (the first mutable master-data-shaped model that exists in Phase 0).

## ADR-11 — Frontend salvage (scoped)

**Context.** The repo's `resources/js/components` and `composables` contained a sizeable UI kit carried over from an unrelated fuel-station project, with broken imports and fuel-domain role logic mixed in.
**Decision, executed.**
- Deleted (confirmed dead, fuel-domain-specific, referenced nowhere else): `TransactionSummaryCard.vue`, `TransactionSummaryModalHost.vue`, `useTransactionSummaryModal.ts`, `useGender.ts`.
- Rewrote `usePermission.ts` for DMS roles (`super_admin`, `warehouse_manager`, `dsr`, `accountant`, `wholesale_cashier`), backed by `auth.role` / `auth.permissions` now shared from `HandleInertiaRequests` (via `spatie/laravel-permission`'s `getRoleNames()`/`getAllPermissions()`, added to `User` via the `HasRoles` trait).
- Fixed the one call site (`SideNav.vue`) that broke from the `usePermission.ts` rename (it destructured an unused `isAdmin`/`userRole` pair — removed, nothing used them).
**Decision, deferred (documented, not fixed).** `npm run types:check` (`vue-tsc --noEmit`) has ~39 pre-existing errors unrelated to the above:
- `components/reports/*`, `components/dashboard/*`: generic, DMS-reusable (`ReportKpi`/`ReportTable`/`ReportChart` shapes have no fuel-specific fields) but need a `@/types/reports.ts` + `@/types/shared.ts` that don't exist yet, and `ForecastChart.vue` needs the `apexcharts`/`vue3-apexcharts` npm packages which aren't installed. Wiring this is Phase 8 (Dashboards) work, not Phase 0 foundation — left as-is.
- `Breadcrumb.vue` (`Auth.station`/`Auth.company` don't exist on the new `Auth` type), `DataTable/*` (`@/types` has no `ColumnDef` export; a `Request` type mismatch), `useNotifications.ts` (missing Wayfinder route `@/routes/notifications`), `useSort.ts` (same `Request` mismatch) — genuinely pre-existing, unrelated to DMS/RIMS, out of scope for Phase 0. Will be fixed page-by-page as each phase's UI actually touches these components, per the "don't fix things beyond what the task requires" principle.
**Consequence.** `npm run lint:check` (ESLint) is separately broken: `package.json` pins `eslint-plugin-import@^1.14.0` (a package version from ~2015) against `eslint@^10.8.0`; no published `eslint-plugin-import` version supports ESLint 10 yet. `npm install` requires `--legacy-peer-deps` as a result. Not fixed — real fix is either dropping `eslint-plugin-import` for `eslint-plugin-import-x` or downgrading ESLint, both a larger call than Phase 0 warrants.

## ADR-12 — Local environment bootstrap (this machine)

- **PHP patch mismatch.** `composer.lock` pinned packages requiring PHP ≥8.4.1; local PHP is 8.4.0. Resolved with `--ignore-platform-req=php` on composer operations (a lock-file/installer flag, not a `composer.json` dependency change) — the gap is a patch version, not a real incompatibility.
- **npm peer conflict.** See ADR-11's consequence above; resolved with `--legacy-peer-deps`.
- **OneDrive read-only attribute.** This repo lives under OneDrive, which had stamped the Windows read-only attribute on `bootstrap/cache/` and `storage/`, causing PHP's `is_writable()` to report `false` even though the underlying NTFS ACLs allowed writes (confirmed via `attrib`). Cleared with `attrib -R ... /S /D`. If this recurs after a OneDrive re-sync, re-run the same `attrib -R` command on those two directories.

## ADR-13 — Migration sequencing corrections (vs. the original phase plan)

The build plan listed `warehouse_user` and full FK constraints on `stock_ledger`/`stock_balances`' `product_id`/`batch_id` under Phase 0. Both were deferred to Phase 1 since they reference tables (`warehouses`, `products`, `batches`) that don't exist until Phase 1 creates them. Phase 1 must add the FK constraints via an additive migration once those tables land — tracked as a Phase 1 to-do, not forgotten scope.

## ADR-14 — PHPStan raised to level 8 immediately

Bumped from the repo's starting level 7 straight to level 8 (per the build's engineering standard) in Phase 0 rather than deferring, and every resulting error fixed at the source: explicit return types on the two pre-existing stub controllers, `@return X<Y, $this>` generics on every Eloquent relation method, `numeric-string`-typed BCMath call sites in `Quantity`, and rewriting the trigger migration to use literal (non-interpolated) SQL strings instead of a `foreach` over table names. No `@phpstan-ignore` or baseline entries were used.

---

# Phase 1 — Master Data

## ADR-15 — Deferred FK backfill lands, with a SQLite side effect

**Context.** ADR-13 deferred `warehouse_user` and the `stock_ledger`/`stock_balances` → `products`/`batches` foreign keys to Phase 1, once those tables would exist.
**What happened.** Both landed as planned (`2026_07_31_070001_create_warehouse_user_table`, `2026_07_31_070012_add_product_batch_foreign_keys_to_stock_tables`). The FK-add migration broke `StockLedgerImmutabilityTest`'s DB-layer assertions: SQLite has no `ALTER TABLE ADD CONSTRAINT`, so Laravel adds a foreign key to an *existing* SQLite table by rebuilding it (rename → recreate → copy → drop) — which silently drops any triggers attached to that table. The Phase 0 append-only triggers on `stock_ledger` (from migration `060508`) were casualties; `journal_lines` was untouched by this migration so its triggers survived.
**Fix.** `2026_07_31_070013_recreate_stock_ledger_immutability_triggers` re-creates both triggers, `DROP TRIGGER IF EXISTS` first so it's idempotent on MySQL too (where the FK add doesn't rebuild the table and the triggers never actually vanished).
**Takeaway for later phases.** Any future migration that adds a constraint to an existing table via `Schema::table()` on SQLite should be checked for the same trigger-loss side effect.

## ADR-16 — Eloquent `create()` doesn't reflect DB column defaults

`RegisterWarehouse`, `RegisterVan`, `CreateProduct`, `CreateSupplier`, `CreateCustomer` all write to a column with a migration-level `->default(true)` (`is_active`). Eloquent's `create()` returns the in-memory model with only the attributes you explicitly set — it does not re-fetch DB-applied defaults — so `$warehouse->is_active` was `null` on the returned instance even though the stored row had `1`. Fixed by setting `is_active => true` explicitly in every Action that creates one of these models, rather than relying on the DB default. Caught by `WarehouseManagementTest`'s `'a warehouse can be registered'` test.

## ADR-17 — Added a minimal login flow

**Context.** `routes/auth.php` was empty and no route in `routes/web.php` had `auth` middleware — there was no way for any user to authenticate, at all. This wasn't specific to Phase 1, but it blocks meaningfully verifying anything built so far (RBAC, warehouse scoping) outside of Pest's `actingAs()`.
**Decision.** Added `App\Http\Controllers\Auth\LoginController` (session-based `Auth::attempt()`, rate-limited 5/min on the POST route) and `resources/js/pages/Auth/Login.vue`, and wrapped `dashboard`/`profile`/the five new resource route groups in `auth` middleware. No registration or password-reset flow — the spec has admins provisioning users, not self-registration, so those aren't needed yet.

## ADR-18 — Frontend: DataTable made usable, deliberately without its pagination machinery

- Added the missing `ColumnDef` type export to `resources/js/types/index.ts` (`string | {label, key}`) — `BaseTable.vue`/`DataTable.vue` already expected it; without it, any new page importing it would carry a genuinely new (not pre-existing) type error.
- Phase 1 list pages use `BaseTable` directly (static, unpaginated arrays) rather than the full `DataTable` wrapper, which pulls in `useSort`/`SimplePagination` — both hit the pre-existing `Request` type collision documented in ADR-11 (a same-named global DOM type shadowing the custom `@/types` one). Sidestepping it avoids inheriting that bug into working new pages. Real search/sort/pagination is deferred to the Phase 8 hardening pass; current master-data volumes don't need it yet.
- Wired the previously-dead `message` flash-toast prop: `HandleInertiaRequests::share()` now sends `success`/`error`/`warning`/`info` from the session, which `MainLayout.vue`/`AuthLayout.vue` were already watching for but never received. Phase 1 controllers rely on it (`->with('success', ...)` on every redirect).
- `SideNav.vue`'s nav list was hardcoded to `roleMenus.default()` with an unused `permissions` variable — actually wired it to `usePermissions().can()` so items disappear for roles lacking the permission, and populated `navigation.ts` with the Warehouses/Vans/Products/Suppliers/Customers menu.

---

# Phase 2 — Warehouse Inbound (PO, GRN, Purchase Returns)

## ADR-19 — Backend-only phase, by design (superseded for the PO web layer)

Unlike Phase 1, the approved plan's Phase 2 section has no "Web" bullet — the backend (migrations, models, transition guards, Actions, posting rules, tests) was built with no controllers/Inertia pages of my own. A `PurchaseOrderController` + `purchase-orders/{Index,Create,Show}.vue` + `StorePurchaseOrderRequest` were added directly on top of it (same session, different part of the work) — `index/create/store/show` plus `submit`/`approve`/`cancel` action routes, matching the show-page pattern (`AdjacentRecordResolver` prev/next nav, `Card`/`Badge`/`DetailField`/`DetailNav`) already established across the Phase 1 controllers. GRN and Purchase Return still have no web layer — they're the Phase 8-or-later addition now.

## ADR-19a — Generalized AdjacentRecordResolver and fixed non-null relation typing app-wide

Two PHPStan issues surfaced once the PO controller (and the Show-page pattern generally) landed:
- `AdjacentRecordResolver::resolve()` was typed `Builder<Model> $query`, which PHPStan's non-covariant `Builder` template rejects for every concrete caller (`Builder<Customer>`, `Builder<Warehouse>`, etc. are not subtypes of `Builder<Model>`). Fixed by making the method itself generic: `@template TModel of Model` with `Builder<TModel>`/`TModel $model` — this was breaking all six controllers using it (Warehouse, Van, Product, Supplier, Customer, PurchaseOrder), not just the new one.
- Required (non-nullable) `belongsTo` FKs accessed as magic properties (`$purchaseOrder->supplier`, `->warehouse`; `$purchaseOrderItem->product`) are inferred nullable by PHPStan unless the model's docblock says otherwise — same class of issue as `PurchaseReturn.grn` in ADR (Phase 2, `PurchaseReturn` model). Added `@property-read` annotations to `PurchaseOrder` and `PurchaseOrderItem` for the FKs that are genuinely guaranteed once persisted. Worth checking for the same pattern on any future model with a required `belongsTo` accessed via magic property.

## ADR-20 — StockMover: atomic SQL increments instead of lockForUpdate()-then-write

**Context.** Every stock-moving Action needs to update `stock_balances.qty_on_hand` without losing updates under concurrent writers (the plan explicitly calls for a `GrnConcurrentReceiptTest`).
**Decision.** `App\Modules\Warehouse\Domain\StockMover::move()` does not read-then-write in PHP. It issues a single parameterised `UPDATE stock_balances SET qty_on_hand = qty_on_hand + ? WHERE ...` (via `DB::affectingStatement()`, numeric string bound directly — never cast through a PHP float). If it affects 0 rows (no balance row yet), it creates one, retrying up to 3 times on a unique-constraint race against a concurrent first-creation. The same pattern (`PurchaseOrderItem.qty_received += ?` via `DB::statement()`) is used in `PostGrn` for the PO item counter.
**Why not `Eloquent::increment()`.** Its `$amount` parameter is typed `float|int`; passing our BCMath-exact `Quantity` string would coerce through a PHP float, reintroducing exactly the precision risk `Quantity`/`Money` exist to prevent. A raw parameterised statement keeps the arithmetic in the database, at full decimal precision, for both correctness and performance under contention — no `lockForUpdate()` needed for these counters.
**Where `lockForUpdate()` is still used.** `PostGrn::recomputePurchaseOrderStatus()` locks the *parent* `PurchaseOrder` row before reading all its items and deciding the new status — that's a read-then-decide, not an accumulate, so it genuinely needs a lock to serialize concurrent GRN postings against the same PO.
**Verified by.** `GrnConcurrentReceiptTest.php` — see that file's docblock for why it tests sequential accumulation exactness rather than literal thread concurrency (SQLite `:memory:` makes multi-connection testing meaningless: separate connections are separate databases).

## ADR-21 — Chart of Accounts resolved by configured code, not hardcoded IDs or a per-supplier account

**Context.** GRN-03 requires posting Dr Inventory / Cr Accounts Payable (supplier). FIN-04 requires the AP control account to reconcile to a per-supplier sub-ledger.
**Decision.** Rather than giving every `Supplier` its own `chart_of_accounts` row (which `suppliers.coa_account_id` could support but isn't required to), GRN/Purchase-Return posting rules use **one shared AP control account**, tagging each line with `partner_type = Supplier::class, partner_id = $supplier->id` (the polymorphic `partner_*` columns built in Phase 0). The supplier "sub-ledger" is just `journal_lines` filtered by `account_id = AP control` and grouped by `partner_id` — exactly how real double-entry systems implement control accounts. `App\Modules\Finance\Domain\ChartOfAccountResolver` resolves the canonical Inventory/AP accounts by the `code` configured in `config/accounting.php` (`ACCOUNTING_INVENTORY_ACCOUNT_CODE`/`ACCOUNTING_AP_ACCOUNT_CODE` env overrides), never a hardcoded ID. `database/seeders/ChartOfAccountSeeder` seeds the minimal two accounts this phase's posting rules need; the fuller standard template (FIN-01) is still Phase 6.
**Verified by.** `GrnPostsBalancedJournalTest.php`'s "AP control account reconciles to the sum of its supplier sub-ledger lines" test.

## ADR-22 — Two exception styles for two different kinds of "no"

`CreateDirectGrn` uses `Gate::authorize('grn.direct.create')` (throws Laravel's `AuthorizationException`) because that check is a pure permission gate with no business-rule nuance. `CreateGrnFromPo`'s over-receipt check throws the custom `OverReceiptNotAllowedException` instead, because "don't over-receive" is a business rule with a permission-gated escape hatch (`grn.override`), not a bare authorization check — consistent with `ExpiredBatchException`/`VanHandoverRequiredException` from earlier phases. Same distinction applied to `ApprovePurchaseOrder`: `Gate::authorize('po.approve')` for the plain permission gate, then a separate `SegregationOfDutiesException` (new, in `App\Support\Exceptions` since it's reused across POs/Loadouts/journals) for the creator-cannot-approve-their-own-document rule.

## ADR-23 — Fixed an RBAC over-grant found by the tests meant to prevent it

While writing `GrnOverReceiptTest`/`DirectGrnTest`, both "blocked without permission" assertions failed — not because the permission checks were broken, but because Phase 1's `RolePermissionSeeder` had granted `grn.direct.create` and `grn.override` to the default `warehouse_manager` role, making every warehouse manager already privileged and the checks unreachable. Removed both from the role's default grant; they're now an explicit per-user elevation on top of the role (`$user->givePermissionTo('grn.override')`), matching how the tests — and the spec's framing of these as exceptional escape hatches — actually intend them to work.

---

# Phase 3 — Warehouse Outbound (Sales Order, Proforma, Invoice, Credit Notes)

## ADR-24 — New `Sales` module, depending on Warehouse's `StockMover`/`FefoStockPicker` and Finance's `PostingEngine`

**Decision.** Outbound documents (`SalesOrder`, `ProformaInvoice`, `Invoice`, `CreditNote`) get their own `App\Modules\Sales\` module rather than being added to the already-large `Warehouse` module. `ConvertToInvoice` and `CreateCreditNote` call Warehouse's `StockMover`/`FefoStockPicker` and Finance's `PostingEngine`/`ChartOfAccountResolver` as public services — the established cross-module boundary from ADR-1 (calls only through a module's public Actions/Domain services, never another module's models directly for anything beyond a typed relation).
**Chart of Accounts.** Extended `config/accounting.php` and `ChartOfAccountResolver` with `cashOnHand()`, `accountsReceivable()`, `salesRevenue()`, `cogs()`, alongside Phase 2's `inventory()`/`accountsPayable()` — same "resolve by configured code, never a hardcoded ID" pattern as ADR-21.

## ADR-25 — `credit_limit = 0` means "no credit allowed," not "unlimited"

**Context.** SO-04 requires a credit-limit check before invoicing. A `credit_limit` of `0` is ambiguous in the abstract — it could mean "not configured, don't enforce" or "cash-only customer."
**Decision.** `0` genuinely means **no credit allowed**: `CustomerCreditLimitCheck::wouldExceedLimit()` derives the customer's live outstanding AR balance from `journal_lines` (never a cached column, filtered by the AR account + `partner_type`/`partner_id`) and compares `outstanding + newCharge > credit_limit` — so a `0`-limit customer fails that check on their very first invoice unless paid in full immediately. `ConvertToInvoice` blocks with `CreditLimitExceededException` unless the acting user has `sales.credit_override` (an elevated per-user permission, **not** granted to `warehouse_manager` by default — same discipline as ADR-23, deliberately avoided from the start this time rather than caught by a failing test after the fact).
**Verified by.** `ConvertToInvoiceCreditLimitTest.php` (blocked without override, allowed with it, and `sales.credit_override` absent from the role's default grant).

## ADR-26 — Overpayment is never capped; it becomes the customer's credit balance

**Decision.** `RecordInvoicePayment` never caps a payment at the invoice's remaining balance — it posts the full amount (Dr Cash/Cr AR) regardless, then recomputes the invoice's paid-status from the sum of its payments (`paid` once `totalPaid >= grand_total`). An overpayment simply drives that customer's AR balance negative. No separate "customer credit balance" column or ledger exists — the negative AR balance *is* the credit, queryable via the same `CustomerCreditLimitCheck::outstandingBalance()` used for the credit-limit check, so the two features share one source of truth by construction.
**Verified by.** `InvoiceOverpaymentBecomesCreditTest.php`.

## ADR-27 — Tax rate and per-line rounding: recomputed fresh, never re-rounded at the document level

**Decision, tax rate.** `ConvertToInvoice::convert()` always re-fetches the product's *current* `tax_rate` at conversion time and recomputes `lineGross->percentage($rate)` itself — it never accepts a tax figure from the caller's items array at all (the array only carries `product_id`/`qty`/`unit_price`/`discount`). This removes "stale tax rate copied from the Sales Order" as a possible bug by construction rather than by caller discipline, since a Sales Order can sit in `draft`/`confirmed`/`fulfilled` for an arbitrary time before conversion while tax policy changes.
**Decision, rounding.** Every document total (`subtotal`, `tax_total`, `discount_total`, `grand_total`) is a running sum of already-rounded per-line `Money` amounts — never an independent `documentSubtotal->percentage(rate)` recomputation. The two can legitimately disagree by a pesewa (e.g. summing `round(376.125)+round(188.375) = 564` vs. `round(564.5) = 565` on the combined subtotal); only the per-line-sum approach is correct, since it's what the customer's printed line items actually add up to.
**Verified by.** `TaxRateAtInvoiceDateTest.php` (rate changed between order and conversion; invoice uses the rate at conversion time), `RoundingSumsExactlyTest.php` (the 564-vs-565 case, by exact minor-unit assertion).

## ADR-28 — `Warehouse $warehouse` relation swapped for a raw `warehouse_id` in `ConvertToInvoice`

**Context.** `ConvertToInvoice::convert()` originally took a full `Warehouse $warehouse` object, resolved via `$salesOrder->warehouse` / `$proforma->warehouse` (Eloquent `belongsTo`). The `Warehouse` model carries a `WarehouseScope` global scope (ADR-2, Phase 1) restricting non-super-admin users to warehouses they're assigned to via the `warehouse_user` pivot. A `warehouse_manager` acting on a Sales Order for a warehouse they aren't explicitly pivoted to — a legitimate case, since `CreateSalesOrder`/`CreateProformaInvoice` never required that pivot to exist — got a silent `null` back from the relation, surfacing as a fatal `TypeError` deep in `ConvertToInvoice` rather than a clear authorization error.
**Decision.** `convert()` now takes `int $warehouseId` and both callers pass `$salesOrder->warehouse_id` / `$proforma->warehouse_id` directly — plain columns already on hand, no relation lookup. Every actual use of the parameter inside `convert()` was already just `->id` (document number scope, `Invoice.warehouse_id`, `FefoStockPicker`/`StockMover` location id), so the full model was never needed. This also matches `CreateSalesOrder`/`CreateProformaInvoice`'s existing signatures (`int $warehouseId`), and `CreateCreditNote` was already doing the same thing via `$invoice->warehouse_id`.
**Takeaway for later phases.** Don't resolve a scoped model's relation just to read its `id` inside an already-authorized internal Action — pass the id through from wherever it's already a plain column. If a future Action genuinely needs warehouse *attributes* (name, manager) rather than just its id, that's a real case for revisiting `WarehouseScope`'s reach into internal cross-document lookups, not just this workaround.
**Caught by.** Running the newly-written Phase 3 test suite — `ProformaExpiredConversionBlockedTest`'s non-expired-conversion case failed with exactly this `TypeError` before the fix.

---

# Phase 4 — Warehouse POS

## ADR-29 — Extracted `InvoiceLineComposer`, shared by `ConvertToInvoice` and `RecordPosSale`

**Context.** POS needs the exact same tax-at-conversion-time computation and FEFO-picked stock/line persistence `ConvertToInvoice` already had, but with a different Invoice-header story (no Sales Order/Proforma, no due date, immediate payment).
**Decision.** Extracted the line-computation (`compute()` — pure, no side effects, tax/discount/subtotal totals) and line-persistence (`persist()` — FEFO pick + `StockMover` + `InvoiceItem::create`) out of `ConvertToInvoice::convert()` into `App\Modules\Sales\Domain\InvoiceLineComposer`. `ConvertToInvoice` was refactored to use it (re-verified against the full Phase 3 suite — zero regressions); `RecordPosSale` uses the same two methods. Unlike the Phase 3 extractions, this wasn't speculative — two concrete callers needed the identical logic today.

## ADR-30 — POS credit-limit check nets out same-transaction payments; `ConvertToInvoice`'s does not

**Context.** `ConvertToInvoice`'s credit check (ADR-25) compares the full invoice `grand_total` against the customer's credit limit before any payment — correct for a wholesale credit sale, where the invoice is created `unpaid` and paid later. A POS sale is different: payment is (usually) collected in the same transaction as the sale. Applying the gross-total check to POS would block an ordinary fully-paid cash sale for any walk-in/`credit_limit = 0` customer, since the gross total always "exceeds" a zero limit before the payment is counted.
**Decision.** `RecordPosSale` computes `remainingAfterPayment = grandTotal - Σpayments` and only runs the credit-limit check (and only requires `sales.credit_override` to bypass it) against that remainder. A fully-paid POS sale never touches the credit check at all (remainder ≤ 0). A partially-paid POS sale is checked exactly like a wholesale credit sale would be, on the unpaid remainder — same rule, same override permission, just evaluated after netting the immediate payment instead of before it.
**Verified by.** `PosSplitPaymentTest.php` (fully-paid `credit_limit=1000` customer; a partial payment leaves the invoice `partially_paid` without needing an override since it's within the limit), `PosSaleConcurrentLastUnitTest.php`.

## ADR-31 — POS discount-override threshold: a config-driven percentage, not a copy of the credit-limit pattern

**Context.** POS-04 requires price/discount control with an escape hatch, mirroring the `grn.override`/`sales.credit_override` shape (ADR-23/25) but for a different quantity — a discount *rate*, not a monetary limit tied to a specific customer.
**Decision.** `config('sales.pos_discount_override_threshold_percent')` (env `SALES_POS_DISCOUNT_OVERRIDE_THRESHOLD_PERCENT`, default 10) is compared per-line against `discount / (unit_price × qty) × 100`, computed from already-known `Money`/`Quantity` values (not re-derived from stored minor units in a way that could round differently). Exceeding it without the new `pos.discount_override` permission throws `DiscountOverrideRequiredException`. `pos.discount_override` is **not** granted to `wholesale_cashier` by default — same discipline as every other elevation in this codebase (ADR-23/25), applied from the start this time.
**Verified by.** `PosDiscountOverrideThresholdTest.php`.

## ADR-32 — Till variance posts to a single "Cash Over/Short" account, both directions

**Context.** POS-03's X/Z report needs to post the difference between a till's expected and counted cash to the general ledger.
**Decision.** One account (code `5900`, type `expense` by convention) absorbs both shortages (Dr Cash Over/Short / Cr Cash on Hand) and overages (Dr Cash on Hand / Cr Cash Over/Short) — standard retail-accounting practice, since till variances are normally small and their net effect across many sessions is what matters, not classifying each occurrence separately. `CloseTillSession` only calls `PostingEngine::post('till.closed_with_variance', ...)` when `variance !== 0`; a balanced till posts nothing (`PostingEngine`/`JournalLineData` already reject a zero-amount line — see Phase 0's edge cases — so this skip is required, not just tidy).
**Verified by.** `TillZReportVarianceTest.php` (shortage, overage, and a zero-variance case verified indirectly via `TillSessionLifecycleTest.php`'s "posts nothing" assertion).

## ADR-33 — `TillSessionTransitions` deliberately does not treat same-state as a no-op

**Context.** Every other `*Transitions` guard (`PurchaseOrderTransitions`, `SalesOrderTransitions`, `InvoiceTransitions`) short-circuits `assertCanTransition($from, $to)` when `$from === $to`, treating a same-state call as a harmless idempotent retry. `TillSessionTransitions` was written the same way initially.
**Problem found.** `CloseTillSession`'s body always recomputes the expected float and conditionally re-posts the variance journal after calling the guard — the guard merely *returning* on `'closed' -> 'closed'` (instead of throwing) doesn't stop the rest of the method from running. A second `CloseTillSession` call against an already-closed session would have silently recomputed and potentially double-posted a variance journal entry — a real financial-integrity bug, caught by `TillSessionLifecycleTest`'s "closing an already-closed till session is blocked" test failing.
**Decision.** `TillSessionTransitions::assertCanTransition()` has no same-state shortcut — `'closed' -> 'closed'` throws `IllegalTransitionException` like any other disallowed transition, since `ALLOWED['closed'] = []`. This is a **deliberate divergence** from the other three guards, not an oversight; the shared "same-state is a no-op" convention is only safe when the calling Action's body is itself idempotent on a repeat call, which `CloseTillSession`'s is not.
**Takeaway for later phases.** Before reusing the same-state-no-op convention on a new `*Transitions` guard, check whether the Action calling it does real, non-idempotent work (side effects, postings) after the guard call — if so, favor `TillSessionTransitions`'s stricter shape instead.
**Verified by.** `TillSessionLifecycleTest.php`.

## ADR-34 — Reinterpreted `SessionExpiryMidSaleTest` and `PosSaleConcurrentLastUnitTest` for what the backend actually guarantees

**Context.** The build plan names these tests for behavior a POS *frontend* would own (an in-browser cart surviving a session timeout; two physical tills racing on the same network). Phase 4, like Phase 2/3, has no web/UI layer — there's no cart-persistence backend and, per `decisions.md` ADR-20's already-established reasoning, SQLite `:memory:` makes literal multi-connection concurrency testing meaningless (separate connections are separate databases).
**Decision.** Reinterpreted both around the actual backend guarantee, the same way Phase 3 reinterpreted `UnitConversionSaleFromCartonTest` (ADR-27's sibling case, not separately numbered): `SessionExpiryMidSaleTest` proves a sale attempted against a *closed* till session is rejected and leaves zero partial `Invoice`/stock-ledger/journal rows behind (the transaction-atomicity guarantee that actually matters once a real frontend adds session-expiry-triggered till closure). `PosSaleConcurrentLastUnitTest` proves sequential last-unit contention is rejected on the second attempt, mirroring `GrnConcurrentReceiptTest`'s documented precedent rather than re-deriving new reasoning.
**Verified by.** `SessionExpiryMidSaleTest.php`, `PosSaleConcurrentLastUnitTest.php`.

---

# Phase 5 — Van / DSR Operations

## ADR-35 — Loadout/loadin restricted to non-batch-tracked products; batch-tracked support deferred

**Context.** `FefoStockPicker` splits a requested quantity across however many batches are needed, producing one `InvoiceItem` row per pick (Sales module). `LoadoutRequestItem`/`LoadinRequestItem` are single rows per product with plain `qty_*` columns — there is no equivalent fan-out structure for a loadout line to record "3 units from batch A, 2 from batch B."
**Decision.** `RequestLoadout` and `RequestLoadin` reject any line whose product has `track_expiry = true` outright (`BatchTrackedLoadoutUnsupportedException`), rather than silently moving stock against `batch_id = 0` (which would either corrupt a real batch-tracked balance row or silently create a bogus untracked one). All loadout/loadin stock movements use `batchId: null` unconditionally.
**Consequence.** Van loadouts of batch/expiry-tracked products are out of scope for Phase 5. A future FEFO-aware loadout would need `LoadoutRequestItem` to support multiple batch-split sub-rows per requested line, mirroring `InvoiceItem`'s relationship to a Sales Order line — a real schema change, not a small addition, deferred until actually needed.
**Verified by.** All Phase 5 tests deliberately use non-tracked products (`createProductFixture()`'s default `trackExpiry: false`, or a custom `CreateProduct` call without `track_expiry`).

## ADR-36 — `StockAvailabilityCheck` extracted as a read-only counterpart to `FefoStockPicker`

**Context.** `RequestLoadout`/`ApproveLoadout`/`RequestLoadin` all need to answer "is there enough stock at this location for this product" without actually picking or moving anything (the real movement happens later, at `ConfirmLoadoutReceipt`/`AcceptLoadin` — see ADR-35's LO-03 note below). `FefoStockPicker::pick()` both validates *and* returns specific batch picks, which is more than these call sites need and doesn't fit the "check now, move later" shape.
**Decision.** `App\Modules\Warehouse\Domain\StockAvailabilityCheck::assertAvailable(locationType, locationId, productId, qty)` — a small, side-effect-free check against the `batch_id = 0` balance row, throwing the same `InsufficientStockException` `FefoStockPicker` uses. Placed in the Warehouse module (not Van) since it's a general stock concern, reusable by any future non-batch-tracked availability check.
**Verified by.** `LoadoutExceedsWarehouseStockTest.php` (both at request and at approval-time qty-edit).

## ADR-37 — LO-03: loadout stock only moves at Received confirmation; the discrepancy is recorded, not absorbed

**Decision.** `RequestLoadout` and `ApproveLoadout` only validate availability and record intent (`qty_requested`/`qty_approved`); `MarkLoadoutLoaded` is a pure status checkpoint recording `qty_loaded` with **no ledger entry**. The only stock movement happens in `ConfirmLoadoutReceipt`: warehouse OUT of `qty_loaded` (what physically left), van IN of `qty_received` (what physically arrived) — using the DSR's confirmed figure, not the loaded figure, for the van side. Any shortfall (`qty_loaded - qty_received`) is captured via `LoadoutRequestItem::discrepancy()` but is not booked anywhere else: it simply isn't in either location's balance, matching physical reality (goods lost in transit aren't "in stock" anywhere).
**Verified by.** `LoadoutPartialConfirmationDiscrepancyTest.php` (warehouse loses the full loaded qty; van gains only the received qty; the discrepancy is queryable, not silently zero).

## ADR-38 — `LoadoutTransitions`/`LoadinTransitions` follow `TillSessionTransitions`'s no-same-state-no-op rule

**Decision.** Both new guards omit the `$from === $to` shortcut used by `PurchaseOrderTransitions`/`SalesOrderTransitions`/`InvoiceTransitions` (ADR-33's precedent) — `ConfirmLoadoutReceipt` moves real stock and `AcceptLoadin` moves stock *and* conditionally posts a write-off, so a silent no-op on a repeat call (`'received' -> 'received'`, `'accepted' -> 'accepted'`) would risk double-moving stock or double-posting. Applied proactively this time, not discovered by a failing test — see ADR-33's own "takeaway for later phases" note, which this directly follows.

## ADR-39 — A third stock location type, `'damages'`, added by widening the existing enum

**Context.** LI-02 calls for damaged loadin stock to move to a "damages virtual location," distinct from `'warehouse'`/`'van'`. `stock_ledger.location_type`/`stock_balances.location_type` are DB-level `enum('warehouse', 'van')` columns.
**Decision.** Widened both enums to `('warehouse', 'van', 'damages')` via a dialect-branching migration (native `ALTER ... MODIFY` on MySQL; Laravel's `Blueprint::enum()->change()` on SQLite, which rebuilds the table). Damaged stock uses `location_type = 'damages'`, `location_id = warehouse_id` — one damages location per warehouse, the same `location_id` convention `'warehouse'` itself uses.
**Risk, mitigated.** Per ADR-15, any SQLite table rebuild silently drops triggers attached to that table — `stock_ledger`'s append-only triggers were a casualty here too, exactly as they were for the FK-add migration in Phase 1. A follow-up migration re-creates them (`2026_07_31_110006_...`), identical in approach to `2026_07_31_070013_...`. Re-verified via `tests/Feature/Foundation/StockLedgerImmutabilityTest.php` after both migrations ran.

## ADR-40 — Added `RecordVanSale`, not named in the build plan's Phase 5 Action list, because the settlement identity needs real sales data

**Context.** The plan's `GenerateDsrSettlement` formula ("opening + loadouts − sales − loadins = closing") references "sales" as an input, but no Action creates a van-sourced sale — `ConvertToInvoice`/`RecordPosSale` (Phases 3-4) only ever draw stock from a warehouse.
**Decision.** Added `App\Modules\Sales\Actions\RecordVanSale`, mirroring `RecordPosSale`'s shape (immediate posting, no draft/confirm lifecycle, credit-limit check nets out same-transaction payments — ADR-30's reasoning applies identically to a DSR field sale) but keyed to a `van_storage_id` instead of a `till_session_id`. Required generalizing `InvoiceLineComposer::persist()` from a hardcoded `'warehouse'` location to an explicit `(string $locationType, int $locationId)` pair — `ConvertToInvoice`/`RecordPosSale` updated to pass `'warehouse'` explicitly; both re-verified against their full test suites (zero regressions).
**Schema.** `invoices.source` enum widened to add `'van'`; `invoices.van_storage_id` nullable FK added (same dialect-branching migration pattern as ADR-39, but no trigger-recreation follow-up needed since `invoices` has no triggers).
**Verified by.** `DsrSettlementIdentityInvariantTest.php`, `CashShortOverVarianceSignoffTest.php`.

## ADR-41 — `GenerateDsrSettlement`'s stock-value identity is computed entirely from `stock_ledger`, not an independent valuation

**Context.** "opening + loadout − sales − loadin = closing" could be verified two ways: (a) compute all four terms from `stock_ledger` and check they sum correctly, or (b) compute opening/movements from the ledger and independently value the *live* `stock_balances.qty_on_hand` against some costing method (FIFO, weighted-average) to cross-check `closing`.
**Decision.** Went with (a). No inventory valuation method exists anywhere in this codebase yet — `stock_balances` tracks quantity only, and every cost figure elsewhere (`InvoiceItem.unit_cost`, `LoadoutRequestItem.unit_cost`, ...) is a per-movement snapshot of `Product.cost_price` at that moment, not a computed running value. Introducing a valuation method just for this one identity check would be new, undesigned scope. `DsrSettlementIdentityInvariantTest` is therefore a regression guard on the settlement's own aggregation logic (four `stock_ledger` sums, scoped correctly by `doc_type` and date range, compose into the expected total) rather than an independent physical reconciliation.
**Sign-off folded into the same Action.** The plan names only `GenerateDsrSettlement`, not a separate sign-off Action. Passing an optional `$cashCounted` computes the variance and marks the settlement `signed_off` in the same call; omitting it leaves a `generated` settlement's cash fields untouched on a later re-generation (`updateOrCreate` keyed on `(van_storage_id, settlement_date)`, unique-constrained at the DB level so a given van/day has exactly one settlement row).
**Bug found and fixed.** The existing-settlement lookup originally compared `where('settlement_date', $date->toDateString())` against Eloquent's own `'date'`-cast column — but the cast's *write* path stores a full `Y-m-d H:i:s` string, not a bare date, so the plain equality lookup never matched and every call attempted a fresh `INSERT`, colliding with the unique constraint on the second call for the same van/day. Fixed with `whereDate('settlement_date', ...)`, which compares only the date portion regardless of how the column is actually stored — caught by `DsrSettlementIdentityInvariantTest`'s and `CashShortOverVarianceSignoffTest`'s "generate twice" cases.
**Verified by.** `DsrSettlementIdentityInvariantTest.php`, `CashShortOverVarianceSignoffTest.php`.

---

# Phase 6 — Financial Management

## ADR-42 — Fiscal periods are opt-in: unconfigured dates stay unrestricted

**Context.** FIN-05 requires posting blocked into a closed fiscal period. `journal_entries.fiscal_period_id` has existed as a nullable FK since Phase 0, but `PostingEngine::post()` never set it — every prior phase's tests post freely with zero `FiscalPeriod` rows ever seeded.
**Decision.** `PostingEngine::post()` now resolves the `FiscalPeriod` covering the posting date (`starts_on <= date <= ends_on`) on every call. If none exists, posting proceeds unrestricted (`fiscal_period_id` stays null) — periods are something an accountant opts into by creating them, not a mandatory gate from day one. If one exists and is closed, `PostingIntoClosedPeriodException` is thrown *before* the transaction opens. This kept all 175 pre-Phase-6 tests passing unmodified, since none of them seed a `FiscalPeriod`.
**Verified by.** `PostingIntoClosedPeriodRejectedTest.php` (both the closed-period block and the no-period-configured passthrough).

## ADR-43 — `ReverseJournal` reuses `PostingEngine`, not a hand-rolled `JournalEntry::create`

**Decision.** Rather than writing a second, parallel path that directly creates a `JournalEntry`/`JournalLine` pair, `ReverseJournal` introduces `JournalReversalPostingRule` (reads the original journal's own lines, swaps debit↔credit) and calls `PostingEngine::post('journal.reversed', $originalJournal)` like every other posting. This means reversals get fiscal-period enforcement and the balance assertion for free, and there is exactly one code path that ever writes to `journal_entries`/`journal_lines` — not two. `reversal_of_id` (a plain FK, not something `PostingEngine::post()`'s generic signature knows about) is set via a follow-up `update()` after the post call returns.
**Guard.** New `JournalEntryTransitions` (posted → reversed only) follows `TillSessionTransitions`'s no-same-state-no-op rule (ADR-33/38): reversing an already-reversed entry must throw, not silently no-op, since the Action's body does real work on every call.
**Verified by.** `ReversalOfReversalTest.php` — reversing a reversal reproduces the original's exact lines (double negation), and reversing the same entry twice is blocked.

## ADR-44 — Manual journal approval: `PostManualJournal` decides immediate-vs-held by comparing against a config threshold; `ApproveManualJournal` is a separate, plan-unlisted Action

**Context.** The plan names only `PostManualJournal` ("approval required above a configurable threshold"), but a threshold-gated approval workflow structurally needs two actors: whoever requests it, and — only above the threshold — a different person who approves it before it actually posts.
**Decision.** `PostManualJournal` always persists a `ManualJournal` + its `ManualJournalLine` rows (audit trail exists regardless of outcome) and balance-checks before persisting anything (`UnbalancedJournalException`, same as `PostingEngine`'s own check). If `total_debit <= config('finance.manual_journal_approval_threshold')` (default 5000.00 GHS), it immediately calls `PostingEngine::post('manual_journal.posted', ...)`. Above it, the journal stays `pending_approval` with no `journal_entries` row until the new `ApproveManualJournal` Action — gated by the same `journal.post` permission, enforcing `requester_id !== approver_id` (`SegregationOfDutiesException`, the same exception class reused from Purchase Orders/Loadouts) — calls `PostingEngine::post()` itself. `ManualJournalPostingRule` converts the already-balance-checked `ManualJournalLine` rows into `JournalLineData` 1:1, so both the immediate and approved paths post through the exact same rule.
**Verified by.** `UnbalancedManualJournalRejectedTest.php`, `JournalApprovalThresholdSegregationTest.php`.

## ADR-45 — DSR cash chain: one shared "Cash in DSR Hand" control account, tagged per-DSR; one journal per collection, not a lump handover

**Context.** BNK-06 wants a DSR's field cash collections traced through collection → handover → deposit, to a person at every hop, without inventing a new "virtual account per DSR."
**Decision.** Reused the exact control-account pattern from AP/AR (ADR-21): one shared `Cash in DSR Hand` account (code `1020`), with every journal line against it tagged `partner_type = User::class, partner_id = $dsrUserId`. `RecordDsrCollection` posts Dr Cash-in-DSR-Hand(dsr)/Cr AR(customer) — the customer's balance clears immediately even though the cash hasn't reached the business yet. `HandoverDsrCash` hands over *every* `with_dsr` collection for a DSR in one call, but posts **one journal per collection**, not one lump-sum entry — preserving per-collection traceability (which specific sale's cash was handed over when) rather than collapsing it into an aggregate figure that can't be traced back to an individual collection.
**`Collection.warehouse_id` captured at collection time, not resolved later.** `VanStorage` carries a `VanScope` global scope (ADR-2). Rather than have `DsrCashHandoverPostingRule` resolve `$collection->vanStorage->warehouse_id` (a scoped relation lookup inside an internal posting rule — exactly the shape that caused the real bug in ADR-28), `collections.warehouse_id` is a plain column captured once from the van at `RecordDsrCollection` time. Applied proactively this time, not discovered by a failing test.
**Verified by.** `DsrCashChainTraceabilityTest.php`.

## ADR-46 — `RegisterBankAccount`/`RegisterExpenseCategory` auto-create a dedicated Chart of Accounts row per instance

**Context.** Every account resolved so far (`ChartOfAccountResolver`) is a single, config-coded, shared account (one Cash on Hand, one AP control, ...). Bank accounts and expense categories are different: there can be arbitrarily many of them, each needing its *own* GL account (a bank statement is worthless if three different bank accounts all post to one shared "Bank" line).
**Decision.** `RegisterBankAccount`/`RegisterExpenseCategory` each create a `ChartOfAccount` row (caller-supplied `code`) and the owning `BankAccount`/`ExpenseCategory` row in one transaction, linked via `coa_account_id`. Neither Action is named in the plan's Phase 6 Action list — they're the obvious prerequisite plumbing `RecordBankDeposit`/`RecordExpense` need to exist at all, matching the precedent set by `RecordVanSale` (ADR-40): build what a named, tested requirement structurally depends on, even if the plan's Action list didn't spell out every supporting piece.
**No permission gate.** Matches `CreateUnit`/other simple lookup-creation Actions in this codebase (no `Gate::authorize()` call) rather than inventing an ill-fitting permission slug for "register a bank account."

## ADR-47 — Only `TrialBalance` built from the plan's 10 named report types; the other 9 are deferred, not stubbed

**Context.** The plan lists 10 read-only report query services (TrialBalance, ProfitAndLoss, BalanceSheet, GeneralLedger, CashBook, BankBook, ArApAging, CustomerSupplierStatement, DsrCashPosition, Tax). Only `TrialBalanceBalancesInvariantTest` and `ArApControlReconciliationInvariantTest` are named as Phase 6 tests, and AR/AP control reconciliation doesn't need a dedicated report *class* — it's directly queryable via `journal_lines`, exactly how `GrnPostsBalancedJournalTest` already proved the AP half in Phase 2.
**Decision.** Built `App\Modules\Finance\Domain\Reports\TrialBalance` (a live per-account debit/credit summary from `journal_lines`) and nothing else from that list. Building the other 9 with no test to validate them would violate this project's own standing rule: nothing is marked DONE without a passing test citing it, and speculative untested report classes are exactly the kind of scope CLAUDE.md's "don't build beyond what's asked" guidance rules out. `requirements-matrix.md` marks FIN-01 PARTIAL rather than claiming full report coverage.
**Takeaway for later phases.** Build the remaining report types on demand, each with its own test, rather than pre-building a report library speculatively.

## ADR-48 — Fixed a latent SQLite unique-violation detection bug in three places, discovered while building bank deposit duplicate-slip-reference handling

**Context.** `RecordBankDeposit`'s duplicate-slip-reference test failed even though the correct exception class existed and the catch block looked right. Investigation (empirically reproducing a duplicate-key insert in isolation) showed SQLite's `UNIQUE constraint failed` message **never includes the violated index's name** — not even for an explicitly-named composite unique index — only the bare `table.column[, table.column, ...]` list. MySQL's message *does* include the index name. The existing string-match (`str_contains($message, 'bank_transactions_slip_reference_unique')`) only ever matches MySQL's format.
**Consequence, found by inspection, not a failing test.** The exact same pattern already existed in two Phase 0/2 files — `StockMover::isBalanceUniqueViolation()` and `DocumentNumberGenerator::isSequenceUniqueViolation()` — both guarding a retry-on-race-condition path. On SQLite (this project's test database), the check silently never matches, so a genuine unique-constraint race would propagate as a raw `QueryException` instead of being retried. This never surfaced as a failing test because the retry path needs a true concurrent-insert race to exercise, which — per `GrnConcurrentReceiptTest`'s own documented reasoning (ADR-20) — SQLite's `:memory:` database can't produce (separate connections are separate databases).
**Decision.** Fixed all three call sites to check for *both* the MySQL-style named-index string and the SQLite-style bare `table.column` string, rather than just the one this phase's own code needed. Left the underlying retry *logic* untouched — only the detection string was wrong.
**Verified by.** `DuplicateDepositSlipReferenceBlockedTest.php` directly exercises the new bank-deposit path (a real, easily-reproducible unique violation, unlike the two pre-existing race-only paths, which remain effectively untested for this specific detection branch).

## ADR-49 — `CreateGrnFromPo` only drafts a GRN; `PostGrn` is the separate posting step — a test-writing mistake, not a design flaw

**Context.** Writing `ArApControlReconciliationInvariantTest`, calling only `CreateGrnFromPo` produced zero `stock_ledger`/`journal_entries` rows despite the GRN itself being created successfully (no exception).
**Root cause.** `CreateGrnFromPo` (Phase 2) only ever creates a `draft` GRN — receiving-quantity validation and `GrnItem` creation, nothing else. The actual stock movement and Dr Inventory/Cr AP posting happens in the separate `PostGrn` Action, which this test never called. This is Phase 2's existing, already-tested, correct design (`GrnPostsBalancedJournalTest.php` already calls both in sequence) — not a bug in the posting pipeline itself.
**Fix.** Added the missing `app(PostGrn::class)->execute($grn)` call after each `CreateGrnFromPo` call in the test.
**Takeaway for later phases.** When a new test reuses another module's multi-step document lifecycle (draft → post, request → approve → ...), check that module's own tests for the full call sequence rather than assuming a single "create" Action does everything.
