<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(): Response
    {
        $user = request()->user();

        return inertia('Profile', [
            'user' => $user,
            'breadcrumb' => [
                ['label' => 'Profile', 'url' => ''],
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        // $user = $request->user();
        // $validated = $request->validated();

        // $user->name = $validated['name'];
        // $user->username = $validated['username'];
        // $user->email = $validated['email'];

        // if (! empty($validated['password'])) {
        //     $user->password = $validated['password']; // The password will be automatically hashed by the User model's mutator
        // }

        // // mark email as unverified if the email has changed
        // if($user->isDirty('email')){
        //     $user->email_verified_at = null;

        //     // send reset password link to the new email address
        //     $user->sendEmailVerificationNotification();
        // }

        // $user->save();

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
