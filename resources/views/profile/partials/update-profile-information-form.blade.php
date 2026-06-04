<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            <?php echo __('Profile Information'); ?>
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            <?php echo __("Update your account's profile information and email address."); ?>
        </p>
    </header>

    <form id="send-verification" method="post" action="<?php echo route('verification.send'); ?>">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    </form>

    <form method="post" action="<?php echo route('profile.update'); ?>" class="mt-6 space-y-6">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="_method" value="patch">

        <div>
            <label for="name" class="block font-medium text-sm text-gray-700"><?php echo __('Name'); ?></label>
            <input id="name" name="name" type="text" class="mt-1 block w-full" value="<?php echo old('name', $user->name); ?>" required autofocus autocomplete="name">
            <?php if ($errors->has('name')): ?>
                <div class="mt-2 text-sm text-red-600">
                    <?php foreach ($errors->get('name') as $message): ?>
                        <div><?php echo $message; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <label for="email" class="block font-medium text-sm text-gray-700"><?php echo __('Email'); ?></label>
            <input id="email" name="email" type="email" class="mt-1 block w-full" value="<?php echo old('email', $user->email); ?>" required autocomplete="username">
            <?php if ($errors->has('email')): ?>
                <div class="mt-2 text-sm text-red-600">
                    <?php foreach ($errors->get('email') as $message): ?>
                        <div><?php echo $message; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()): ?>
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        <?php echo __('Your email address is unverified.'); ?>

                        <button type="submit" form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo __('Click here to re-send the verification email.'); ?>
                        </button>
                    </p>

                    <?php if (session('status') === 'verification-link-sent'): ?>
                        <p class="mt-2 font-medium text-sm text-green-600">
                            <?php echo __('A new verification link has been sent to your email address.'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:bg-indigo-600 disabled:opacity-25 transition"><?php echo __('Save'); ?></button>

            <?php if (session('status') === 'profile-updated'): ?>
                <p id="saved-message" class="text-sm text-gray-600"> <?php echo __('Saved.'); ?> </p>
                <script>
                    setTimeout(function() {
                        var msg = document.getElementById('saved-message');
                        if (msg) { msg.style.display = 'none'; }
                    }, 2000);
                </script>
            <?php endif; ?>
        </div>
    </form>
</section>
