# Requirements Matrix — DMS

Source of truth: `DMS Requirements.pdf` (System Requirements & Architecture Specification, DMS & RIMS v1.0). RIMS-only requirements (Section 5, RT-xx) are out of scope for this repo — see `decisions.md` in the build plan and the plan's "Out of scope" section.

Status legend: **DONE** (implemented + test passing) · **PARTIAL** (infrastructure ready, not yet exercised by a real business document) · **PENDING** (not started, phase noted).

## Foundation (no formal requirement ID — enables everything below)

| Item | Module | Status | Test(s) |
|---|---|---|---|
| Immutable, DB-enforced journal lines | Finance | DONE | `tests/Feature/Foundation/JournalImmutabilityTest.php` |
| Immutable, DB-enforced stock ledger | Warehouse | DONE | `tests/Feature/Foundation/StockLedgerImmutabilityTest.php` |
| PostingEngine: balanced-journal invariant | Finance | DONE | `tests/Feature/Foundation/PostingEngineBalancesTest.php` |
| Gapless, scoped document numbering | Support | DONE | `tests/Feature/Foundation/DocumentNumberGeneratorTest.php` |
| Audit trail (create/update/delete) | Support | DONE | `tests/Feature/Foundation/AuditLogTest.php` |
| Money value object (minor units, GHS) | Support | DONE | `tests/Unit/Support/MoneyTest.php` |
| Quantity value object (scale 3) | Support | DONE | `tests/Unit/Support/QuantityTest.php` |
| RBAC primitives (spatie/laravel-permission) wired to Inertia | Identity | DONE | manual: `HandleInertiaRequests` shares `auth.role`/`auth.permissions`; exercised properly once Phase 1 adds real roles/policies |

## 3. Users, Roles and Permissions

| ID | Requirement | Module | Status | Test(s) |
|---|---|---|---|---|
| — | RBAC with granular permissions | Identity | PARTIAL | mechanism in place (ADR-2); real permission strings + policies land in Phase 1 |
| — | Warehouse-level / van-level scoping | Identity | PENDING | Phase 1 (`WarehouseScope`, `VanScope`) |
| — | Approval separation (creator ≠ approver) | Identity | PENDING | Phase 2 (`PurchaseOrder`), Phase 5 (`Loadout`), Phase 6 (journals) |
| — | Full audit trail | Identity | DONE | `tests/Feature/Foundation/AuditLogTest.php` |

## 4.1 Warehouse Management Module

| ID | Requirement | Status | Test(s) |
|---|---|---|---|
| WH-01 | Unlimited warehouses, code/name/location/manager | PENDING (Phase 1) | — |
| WH-02 | Warehouse ↔ van storages (1 van = 1 DSR + 1 parent warehouse) | PENDING (Phase 1) | — |
| WH-03 | Central product catalogue incl. unit conversions | PENDING (Phase 1) | — |
| WH-04 | Batch/lot + expiry tracking, FEFO issuing | PENDING (Phase 1 entity, Phase 3 FEFO logic) | — |
| WH-05 | Immutable stock ledger entry per movement | PARTIAL | ledger infra: `StockLedgerImmutabilityTest.php`; real movements start Phase 2 |
| WH-06 | Reorder-level and expiry alerts | PENDING (Phase 8) | — |
| PO-01 | Supplier master + ledger | PENDING (Phase 1) | — |
| PO-02 | Purchase Orders per warehouse | PENDING (Phase 2) | — |
| PO-03 | PO status lifecycle | PENDING (Phase 2) | — |
| PO-04 | PO printable/emailable PDF | PENDING (Phase 8) | — |
| GRN-01 | GRN against PO or direct receipt | PENDING (Phase 2) | — |
| GRN-02 | GRN captures batch/expiry/cost/invoice ref | PENDING (Phase 2) | — |
| GRN-03 | GRN posts stock + Dr Inventory/Cr AP | PENDING (Phase 2) | — |
| GRN-04 | PO vs GRN discrepancy flagging | PENDING (Phase 2) | — |
| SO-01 | Sales Order lifecycle | PENDING (Phase 3) | — |
| SO-02 | Proforma Invoice (non-posting) | PENDING (Phase 3) | — |
| SO-03 | Convert to Invoice posts stock + AR/Revenue/COGS | PENDING (Phase 3) | — |
| SO-04 | Cash/credit/part payments + credit-limit check | PENDING (Phase 3) | — |
| SO-05 | Credit notes / sales returns with reason codes | PENDING (Phase 3) | — |
| POS-01..05 | Warehouse POS (barcode, payments, till, price control) | PENDING (Phase 4) | — |
| LO-01..04 | Loadout request lifecycle | PENDING (Phase 5) | — |
| LI-01..03 | Loadin request + van settlement identity | PENDING (Phase 5) | — |

## 4.2 DSR / Field Assist Management Module

| ID | Requirement | Status | Test(s) |
|---|---|---|---|
| VAN-01 | Van storage registration | PENDING (Phase 1) | — |
| VAN-02 | Routes/beats | PENDING (Phase 1, light) | — |
| VAN-03 | Retailer/outlet master | PENDING (Phase 1) | — |
| DSR-01..10 | DSR mobile app functions (offline, sync, visits, targets) | PENDING (Phase 7) | — |

## 4.3 Financial Management Module

| ID | Requirement | Status | Test(s) |
|---|---|---|---|
| FIN-01 | Hierarchical Chart of Accounts | PARTIAL | table + model exist; seeded standard template is Phase 1/6 |
| FIN-02 | Every financial event posts a balanced journal automatically; manual journals with approval | PARTIAL | generic engine: `PostingEngineBalancesTest.php`; manual-journal UI + approval threshold is Phase 6 |
| FIN-03 | Posted journals immutable; corrections via reversal only | DONE | `tests/Feature/Foundation/JournalImmutabilityTest.php` (reversal *documents* land Phase 6) |
| FIN-04 | AR/AP sub-ledgers reconcile to control accounts | PENDING (Phase 2 AP, Phase 3 AR, Phase 6 reconciliation) | — |
| FIN-05 | Closeable fiscal periods; posting blocked when closed | PARTIAL | table exists; enforcement is Phase 6 |
| BNK-01..06 | Banks, deposits, withdrawals, transfers, reconciliation, DSR cash chain | PENDING (Phase 6) | — |

## 8. Non-Functional Requirements (the ones Phase 0 already touches)

| Requirement | Status | Test(s) |
|---|---|---|
| Data integrity: stock/journal writes in DB transactions | DONE | `PostingEngineBalancesTest.php` (atomic journal+lines), Actions in later phases wrap `DB::transaction()` per ADR pattern |
| Data integrity: journals always balanced | DONE | `PostingEngineBalancesTest.php` |
| Auditability: immutable stock ledger and journals | DONE | `StockLedgerImmutabilityTest.php`, `JournalImmutabilityTest.php` |
| Auditability: gapless sequential document numbering | DONE | `DocumentNumberGeneratorTest.php` |
| Security: RBAC | PARTIAL | primitives wired (ADR-2); real policies start Phase 1 |

---

**Everything not listed above** (RT-xx / RIMS, the DMS↔RIMS bridge) is explicitly out of scope for this repo per the build plan.
