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
