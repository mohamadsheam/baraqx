@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <h1>Login</h1>

    <form method="POST" action="{{ route('login') }}">
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

        <div class="form-group">
            <label for="password">Password</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required
                class="{{ $errors->has('password') ? 'error' : '' }}"
            >
            @error('password')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Login</button>

        <div class="text-center mt-4">
            <a href="{{ route('password.forgot') }}" class="link">Forgot Password?</a>
        </div>
    </form>
@endsection
