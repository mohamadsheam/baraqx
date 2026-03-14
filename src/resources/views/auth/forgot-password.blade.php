@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <h1>Forgot Password</h1>

    <p class="text-center" style="margin-bottom: 1.5rem; color: #6b7280;">
        Enter your email address and we'll send you an OTP to reset your password.
    </p>

    <form method="POST" action="{{ route('password.forgot') }}">
        @csrf

        <div class="form-group">
            <label for="email">Email Address</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}" 
                required 
                autofocus
                class="{{ $errors->has('email') ? 'error' : '' }}"
            >
            @error('email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Send OTP</button>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="link">Back to Login</a>
        </div>
    </form>
@endsection
