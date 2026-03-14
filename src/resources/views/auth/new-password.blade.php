@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <h1>Reset Password</h1>

    <p class="text-center" style="margin-bottom: 1.5rem; color: #6b7280;">
        Create a new password for your account.
    </p>

    <form method="POST" action="{{ route('password.reset') }}">
        @csrf

        <div class="form-group">
            <label for="password">New Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required
                class="{{ $errors->has('password') ? 'error' : '' }}"
            >
            @error('password')
                <div class="error-message">{{ $message }}</div>
            @else
                <div class="error-message" style="color: #6b7280; font-size: 0.75rem;">
                    Must be at least 8 characters with uppercase, lowercase, and number.
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input 
                type="password" 
                id="password_confirmation" 
                name="password_confirmation" 
                required
                class="{{ $errors->has('password_confirmation') ? 'error' : '' }}"
            >
            @error('password_confirmation')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Reset Password</button>
    </form>
@endsection
