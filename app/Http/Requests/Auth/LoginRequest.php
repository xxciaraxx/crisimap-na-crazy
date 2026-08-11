<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->string('identifier')->toString();
        $user = User::where('email', $identifier)->orWhere('username', $identifier)->first();
        if ($user && ! $user->isApproved()) {
            $message = $user->status === 'pending'
                ? 'Your account is pending approval by the system administrator.'
                : 'Your account request was not approved. Contact your system administrator.';

            throw ValidationException::withMessages(['identifier' => $message]);
        }

        $credentials = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? ['email' => $identifier] : ['username' => $identifier];
        if (! Auth::attempt(array_merge($credentials, ['password' => $this->password, 'status' => 'approved']), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->identifierThrottleKey(), 3600);
            ActivityLog::create(['user_id' => $user?->id, 'action' => 'login_failed', 'description' => 'Failed sign-in attempt for '.$identifier.' from '.$this->ip(), 'subject_type' => User::class, 'subject_id' => $user?->id]);

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->identifierThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5) && ! RateLimiter::tooManyAttempts($this->identifierThrottleKey(), 10)) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(RateLimiter::availableIn($this->throttleKey()), RateLimiter::availableIn($this->identifierThrottleKey()));

        throw ValidationException::withMessages([
            'identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identifier')).'|'.$this->ip());
    }

    public function identifierThrottleKey(): string
    {
        return 'login-identifier:'.Str::transliterate(Str::lower($this->string('identifier')));
    }
}
