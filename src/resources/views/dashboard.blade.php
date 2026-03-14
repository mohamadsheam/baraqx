@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')
    <h1>Welcome to Dashboard</h1>

    <p class="text-center" style="margin-bottom: 1.5rem; color: #6b7280;">
        You have successfully logged in!
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endsection
