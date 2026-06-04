<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700">{{ __('Current Password') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" autocomplete="current-password" />
            @if ($errors->updatePassword->has('current_password'))
                <span class="text-red-600 text-sm">{{ $errors->updatePassword->first('current_password') }}</span>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700">{{ __('New Password') }}</label>
            <input id="update_password_password" name="password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password'))
                <span class="text-red-600 text-sm">{{ $errors->updatePassword->first('password') }}</span>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" autocomplete="new-password" />
            @if ($errors->updatePassword->has('password_confirmation'))
                <span class="text-red-600 text-sm">{{ $errors->updatePassword->first('password_confirmation') }}</span>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
