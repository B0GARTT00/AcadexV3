@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Profile Settings') }}
    </h2>
@endsection

@section('content')
<style>
    .page-wrapper {
        background-color: #EAF8E7;
        min-height: 100vh;
        padding: 0;
        margin: 0;
    }

    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem 1rem;
    }

    .page-title {
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
    }

    .content-wrapper {
        background-color: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-top: 1.5rem;
        font-size: 1rem;
    }

    .content-wrapper label {
        font-size: 1.1rem;
        font-weight: 500;
    }

    .content-wrapper input,
    .content-wrapper select {
        font-size: 1rem;
    }
</style>

<div class="page-wrapper">
    <div class="page-container">
        <!-- Page Title -->
        <h1 class="text-3xl font-bold mb-2 text-gray-800 d-flex align-items-center">
            <i class="bi bi-person-circle text-success me-3 fs-2"></i>
            Profile Account Management
        </h1>
        <p class="text-muted mb-4" style="font-size: 1rem;">Manage your profile information and password securely</p>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Two-step form: Step 1 Profile Info, Step 2 Password Update -->
            <div x-data="{
                step: 1,
                password: '',
                password_confirmation: '',
                current_password: '',
                get passwordValid() {
                    return this.current_password.length > 0 &&
                        this.password.length > 0 &&
                        this.password_confirmation.length > 0 &&
                        this.password === this.password_confirmation;
                }
            }">
                <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                    @csrf
                    @method('patch')
                    <div x-show="step === 1">
                        @include('profile.partials.update-profile-information-form')
                        <div class="flex justify-end mt-6">
                            <button type="button" @click="step = 2" class="px-6 py-2 text-base bg-gradient-to-r from-green-500 to-emerald-600 hover:from-emerald-600 hover:to-green-500 text-white shadow-lg rounded-lg border-0 transition-all duration-200">
                                Next
                            </button>
                        </div>
                    </div>
                    <div x-show="step === 2">
                        <div>
                            <div>
                                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" x-model="current_password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="update_password_password" :value="__('New Password')" />
                                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" x-model="password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" x-model="password_confirmation" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <button type="button" @click="step = 1" class="px-6 py-2 text-base bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                Back
                            </button>
                            <button type="submit" class="px-6 py-2 text-base bg-green-600 text-white rounded-lg hover:bg-green-700" :disabled="!passwordValid">
                                Confirm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
