<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button
        type="button"
        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 active:bg-red-600 disabled:opacity-25 transition"
        x-data="{}"
        x-on:click.prevent="open = true"
    >{{ __('Delete Account') }}</button>

    <!-- Modal -->
    <div x-data="{ open: $errors.userDeletion && Object.keys($errors.userDeletion).length > 0 }" x-show="open" class="fixed inset-0 flex items-center justify-center z-50" style="display: none;">
        <div class="fixed inset-0 bg-gray-600 opacity-75"></div>
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-50">
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-6">
                    <label for="password" class="sr-only">{{ __('Password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4 border-gray-300 rounded-md shadow-sm"
                        placeholder="{{ __('Password') }}"
                    />
                    @if ($errors->userDeletion->has('password'))
                        <span class="text-red-600 text-sm">{{ $errors->userDeletion->first('password') }}</span>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" x-on:click="open = false" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:border-gray-300 focus:ring focus:ring-gray-200 active:bg-gray-200 disabled:opacity-25 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="ms-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 active:bg-red-600 disabled:opacity-25 transition">
                        {{ __('Delete Account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
