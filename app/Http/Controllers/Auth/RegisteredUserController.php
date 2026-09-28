<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * Shows the public account-type selector.
     */
    public function create(): View
    {
        return view('auth.register-choice');
    }

    /**
     * Show a role-specific registration form. The route, not form input,
     * determines the account type.
     */
    public function createCandidate(): View
    {
        return $this->registrationForm(UserRole::Candidate);
    }

    /**
     * Show a role-specific registration form. The route, not form input,
     * determines the account type.
     */
    public function createEmployer(): View
    {
        return $this->registrationForm(UserRole::Employer);
    }

    /**
     * Handle an incoming registration request.
     *
     * Role is ALWAYS set server-side from validated input.
     * Admin / Owner roles are NEVER assignable via this form.
     *
     * @throws ValidationException
     */
    public function storeCandidate(Request $request): RedirectResponse
    {
        return $this->storeForRole($request, UserRole::Candidate);
    }

    /**
     * Register an employer from the dedicated employer endpoint.
     *
     * @throws ValidationException
     */
    public function storeEmployer(Request $request): RedirectResponse
    {
        return $this->storeForRole($request, UserRole::Employer);
    }

    /**
     * @throws ValidationException
     */
    private function storeForRole(Request $request, UserRole $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign role separately — role is NOT in $fillable to prevent mass-assignment.
        $user->role = $role;
        $user->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect($this->redirectPath($user));
    }

    private function registrationForm(UserRole $role): View
    {
        if (! in_array($role, UserRole::publicRegistrationRoles(), true)) {
            abort(404);
        }

        return view('auth.register', [
            'role' => $role,
            'formAction' => $role === UserRole::Candidate
                ? route('register.candidate.store')
                : route('register.employer.store'),
        ]);
    }

    /**
     * Determine the redirect path after registration based on role.
     */
    private function redirectPath(User $user): string
    {
        return match ($user->role) {
            UserRole::Employer => route('employer.dashboard', absolute: false),
            UserRole::Candidate => route('candidate.dashboard', absolute: false),
            default => route('dashboard', absolute: false),
        };
    }
}
