@extends('layouts.guest')

@section('contents')
    <div class="w-full max-w-sm mx-auto text-white">
        <!-- Header Icon -->
        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-700/30 border-2 border-green-500/50">
                <i class="fas fa-envelope-open-text text-3xl text-green-400"></i>
            </div>
        </div>

        <!-- Title -->
        <h2 class="text-2xl font-bold text-center mb-2 text-white">
            {{ __('Verify Your Email') }}
        </h2>

        <!-- Description -->
        <div class="mb-6 text-sm text-white/90 text-center leading-relaxed">
            <p class="mb-3">
                {{ __('Thanks for signing up! Before we submit your account for approval, please verify your email address by clicking on the link we just emailed to you.') }}
            </p>
            <p class="text-white/70">
                {{ __('If you didn\'t receive the email, we will gladly send you another.') }}
            </p>
        </div>

        <!-- Success Message -->
        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 p-4 rounded-lg bg-green-700/20 border border-green-500/30">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                    <p class="text-sm text-green-400 font-medium">
                        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="space-y-3">
            <!-- Resend Button -->
            <form method="POST" action="{{ route('unverified.verification.send') }}" class="w-full">
                @csrf
                <button 
                    type="submit"
                    class="w-full flex items-center justify-center px-4 py-3 bg-green-700 hover:bg-green-800 text-white font-medium rounded-md shadow-md transition-colors duration-200">
                    <i class="fas fa-paper-plane mr-2"></i>
                    {{ __('Resend Verification Email') }}
                </button>
            </form>

            <!-- OR Divider -->
            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/30"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-transparent text-white/70">{{ __('OR') }}</span>
                </div>
            </div>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('unverified.logout') }}" class="w-full">
                @csrf
                <button 
                    type="submit"
                    class="w-full flex items-center justify-center px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-md border border-white/30 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>

        <!-- Help Text -->
        <div class="mt-6 text-center">
            <p class="text-xs text-white/60">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('Check your spam folder if you don\'t see the email') }}
            </p>
        </div>
    </div>
@endsection
