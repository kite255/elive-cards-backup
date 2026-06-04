document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');

    if (loginForm && loginButton) {
        loginForm.addEventListener('submit', function () {
            const originalText = loginButton.textContent.trim();

            loginButton.disabled = true;
            loginButton.classList.add('btn-loading', 'btn-pulse');

            const loadingText = document.createElement('span');
            loadingText.textContent = 'Signing in';

            loginButton.textContent = '';
            loginButton.appendChild(loadingText);

            let dots = 0;

            const dotAnimation = setInterval(function () {
                dots = (dots + 1) % 4;
                loadingText.textContent = 'Signing in' + '.'.repeat(dots);
            }, 300);

            loginButton.dataset.originalText = originalText;
            loginButton.dataset.loadingInterval = dotAnimation;
        });
    }

    window.addEventListener('pageshow', function () {
        if (loginButton) {
            loginButton.disabled = false;
            loginButton.classList.remove('btn-loading', 'btn-pulse');

            if (loginButton.dataset.originalText) {
                loginButton.textContent = loginButton.dataset.originalText;
            }

            if (loginButton.dataset.loadingInterval) {
                clearInterval(Number(loginButton.dataset.loadingInterval));
                delete loginButton.dataset.loadingInterval;
            }
        }
    });
});