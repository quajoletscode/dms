<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Demo logins seeded by DatabaseSeeder — same email/password pairs, kept
     * here (not imported from the seeder) since the seeder isn't meant to be
     * referenced by application code.
     *
     * @var list<array{email: string, label: string, password: string}>
     */
    private const DEMO_ACCOUNTS = [
        ['email' => 'superadmin@example.com', 'label' => 'Super Admin', 'password' => 'Pass$12'],
        ['email' => 'warehouse.manager@example.com', 'label' => 'Warehouse Manager', 'password' => 'Pass$12'],
        ['email' => 'dsr@example.com', 'label' => 'DSR', 'password' => 'Pass$12'],
        ['email' => 'accountant@example.com', 'label' => 'Accountant', 'password' => 'Pass$12'],
        ['email' => 'cashier@example.com', 'label' => 'Wholesale Cashier', 'password' => 'Pass$12'],
    ];

    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'demoAccounts' => $this->availableDemoAccounts(),
        ]);
    }

    /**
     * Only ever shown in local development — this lists real, working
     * credentials, so it must never reach a non-local environment. Only
     * accounts that actually exist in the database are offered, so a fresh,
     * unseeded install doesn't show buttons that fail to log in.
     *
     * @return list<array{email: string, label: string, password: string}>
     */
    private function availableDemoAccounts(): array
    {
        if (! app()->environment('local')) {
            return [];
        }

        $seededEmails = User::query()
            ->whereIn('email', array_column(self::DEMO_ACCOUNTS, 'email'))
            ->pluck('email')
            ->all();

        return array_values(array_filter(
            self::DEMO_ACCOUNTS,
            fn (array $account) => in_array($account['email'], $seededEmails, true),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
