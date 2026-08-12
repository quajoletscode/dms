<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CashInOut;
use App\Modules\Sales\Actions\CloseTillSession;
use App\Modules\Sales\Actions\OpenTillSession;
use App\Modules\Sales\Domain\Exceptions\TillSessionAlreadyOpenException;
use App\Modules\Sales\Domain\Exceptions\TillSessionClosedException;
use App\Modules\Sales\Http\Requests\CashInOutRequest;
use App\Modules\Sales\Http\Requests\CloseTillSessionRequest;
use App\Modules\Sales\Http\Requests\OpenTillSessionRequest;
use App\Modules\Sales\Models\TillSession;
use App\Modules\Warehouse\Domain\WarehouseScope;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Exceptions\IllegalTransitionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TillSessionController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TillSession::class);

        return Inertia::render('sales/till-sessions/Index', [
            'tillSessions' => TillSession::query()
                ->where('user_id', Auth::id())
                ->with('warehouse')
                ->latest('opened_at')
                ->get()
                ->map(fn (TillSession $session) => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'warehouse' => $session->warehouse->only(['id', 'name']),
                    'opening_float' => $session->opening_float->toMajor(),
                    'opened_at' => $session->opened_at->toIso8601String(),
                    'closed_at' => $session->closed_at?->toIso8601String(),
                ]),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        Gate::authorize('create', TillSession::class);

        $openSession = TillSession::query()->where('user_id', Auth::id())->where('status', 'open')->first();

        if ($openSession) {
            return to_route('till-sessions.show', $openSession)->with('info', 'You already have an open till session.');
        }

        return Inertia::render('sales/till-sessions/Create', [
            // WarehouseScope would otherwise return empty for cashiers who
            // aren't pivoted to any warehouse (mirrors PurchaseOrderController).
            'warehouses' => Warehouse::query()
                ->withoutGlobalScope(WarehouseScope::class)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(OpenTillSessionRequest $request, OpenTillSession $action): RedirectResponse
    {
        try {
            $tillSession = $action->execute($request->validated('warehouse_id'), $request->validated('opening_float'));
        } catch (TillSessionAlreadyOpenException $e) {
            return back()->withErrors(['warehouse_id' => $e->getMessage()])->withInput();
        }

        return to_route('till-sessions.show', $tillSession)->with('success', 'Till session opened.');
    }

    public function show(TillSession $tillSession): Response
    {
        Gate::authorize('view', $tillSession);

        $tillSession->load(['warehouse', 'cashMovements', 'invoicePayments.invoice']);

        return Inertia::render('sales/till-sessions/Show', [
            'tillSession' => [
                'id' => $tillSession->id,
                'status' => $tillSession->status,
                'warehouse' => $tillSession->warehouse->only(['id', 'name']),
                'opening_float' => $tillSession->opening_float->toMajor(),
                'closed_float' => $tillSession->closed_float?->toMajor(),
                'expected_float' => $tillSession->expected_float?->toMajor(),
                'variance' => $tillSession->variance?->toMajor(),
                'opened_at' => $tillSession->opened_at->toIso8601String(),
                'closed_at' => $tillSession->closed_at?->toIso8601String(),
                'cash_movements' => $tillSession->cashMovements->map(fn ($movement) => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'amount' => $movement->amount->toMajor(),
                    'reason' => $movement->reason,
                ]),
                'invoice_payments' => $tillSession->invoicePayments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'method' => $payment->method,
                    'amount' => $payment->amount->toMajor(),
                    'invoice_no' => $payment->invoice->no,
                ]),
            ],
        ]);
    }

    public function cashMovement(CashInOutRequest $request, TillSession $tillSession, CashInOut $action): RedirectResponse
    {
        try {
            $action->execute($tillSession, $request->validated('type'), $request->validated('amount'), $request->validated('reason'));
        } catch (TillSessionClosedException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Cash movement recorded.');
    }

    public function close(CloseTillSessionRequest $request, TillSession $tillSession, CloseTillSession $action): RedirectResponse
    {
        try {
            $action->execute($tillSession, $request->validated('counted_float'));
        } catch (IllegalTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('till-sessions.show', $tillSession)->with('success', 'Till session closed.');
    }
}
