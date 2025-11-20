<section>
    <header class="mb-4 bg-transparent">
        <h2 class="h4 fw-semibold text-dark mb-2">
            {{ __('Profile Information') }}
        </h2>
        <p class="text-muted" style="font-size: 1rem;">
            {{ __("Update your account's profile information.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <x-input-label for="middle_name" :value="__('Middle Name')" />
                <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $user->middle_name)" autocomplete="additional-name" />
                <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>

            <div>
                <x-input-label for="role" :value="__('Role')" />
                <x-text-input id="role" type="text" class="mt-1 block w-full bg-gray-100 border-0 text-gray-600 font-semibold" :value="$user->role == 0 ? 'Instructor' : ($user->role == 1 ? 'Chairperson' : ($user->role == 2 ? 'Dean' : ($user->role == 3 ? 'Admin' : ($user->role == 4 ? 'GE Coordinator' : ($user->role == 5 ? 'VPAA' : 'Unknown')))))" readonly />
            </div>

            @if(!$user->isAdmin())
                <div>
                    <x-input-label for="department" :value="__('Department')" />
                    <x-text-input id="department" type="text" class="mt-1 block w-full bg-gray-100 border-0 text-gray-600" :value="$user->department?->department_description" readonly />
                </div>

                @if($user->role != 4)
                <div>
                    <x-input-label for="course" :value="__('Course')" />
                    <x-text-input id="course" type="text" class="mt-1 block w-full bg-gray-100 border-0 text-gray-600" :value="$user->course?->course_description" readonly />
                </div>
                @endif
            @endif
        </div>

        <div class="mb-6">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full bg-gray-100 border-0 text-gray-600" :value="old('email', $user->email)" required autocomplete="username" readonly />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 mt-4">
                {{-- Save button removed; confirmation only on second part (password update) --}}
        </div>
    </form>
</section>
