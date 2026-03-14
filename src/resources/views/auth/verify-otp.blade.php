@extends('layouts.auth')

@section('title', 'Verify OTP')

@section('content')
    <h1>Verify OTP</h1>

    <p class="text-center" style="margin-bottom: 1.5rem; color: #6b7280;">
        Enter the 6-digit OTP sent to<br>
        <strong>{{ $email }}</strong>
    </p>

    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf

        <div class="form-group">
            <label for="otp">OTP Code</label>
            <input 
                type="text" 
                id="otp" 
                name="otp" 
                maxlength="6" 
                pattern="[0-9]*"
                inputmode="numeric"
                required 
                autofocus
                class="{{ $errors->has('otp') ? 'error' : '' }}"
                style="text-align: center; letter-spacing: 0.5rem; font-size: 1.5rem;"
            >
            @error('otp')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" id="verifyBtn">Verify OTP</button>

        <div class="countdown">
            <span id="countdownText">Resend OTP in </span>
            <span id="timer">60</span> seconds
        </div>

        <div class="text-center mt-4">
            <button type="button" id="resendBtn" class="resend-btn" disabled>
                Resend OTP
            </button>
        </div>
    </form>

    <form id="resendForm" method="POST" action="{{ route('otp.resend') }}" style="display: none;">
        @csrf
    </form>
@endsection

@section('scripts')
<script>
    var otpExpiryMinutes = {{ config('auth.otp.expiry_minutes', 10) }};
    var countdownSeconds = otpExpiryMinutes * 60;
    var countdownInterval;

    function startCountdown() {
        var timerElement = document.getElementById('timer');
        var countdownText = document.getElementById('countdownText');
        var resendBtn = document.getElementById('resendBtn');

        countdownInterval = setInterval(function() {
            countdownSeconds--;
            
            var minutes = Math.floor(countdownSeconds / 60);
            var seconds = countdownSeconds % 60;
            timerElement.textContent = minutes + ':' + seconds.toString().padStart(2, '0');

            if (countdownSeconds <= 0) {
                clearInterval(countdownInterval);
                countdownText.textContent = '';
                resendBtn.disabled = false;
                resendBtn.onclick = resendOtp;
            }
        }, 1000);
    }

    function resendOtp() {
        fetch('{{ route("otp.resend") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.error) {
                alert(data.error);
            } else {
                countdownSeconds = otpExpiryMinutes * 60;
                document.getElementById('resendBtn').disabled = true;
                document.getElementById('countdownText').textContent = 'Resend OTP in ';
                startCountdown();
                alert('OTP resent successfully!');
            }
        })
        .catch(function(error) {
            alert('Failed to resend OTP. Please try again.');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var otpInput = document.getElementById('otp');
        
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        startCountdown();
    });
</script>
@endsection
