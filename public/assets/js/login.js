document.getElementById('loginForm').addEventListener('submit', function (e) {
    const button = document.getElementById('loginButton');
    const originalText = button.textContent;

    // Add both loading and pulse effects
    button.classList.add('btn-loading', 'btn-pulse');

    // Create and show the loading dots
    const loadingDots = document.createElement('span');
    loadingDots.textContent = 'Signing in';
    button.textContent = '';
    button.appendChild(loadingDots);

    // Animate the dots
    let dots = 0;
    const dotAnimation = setInterval(() => {
        dots = (dots + 1) % 4;
        loadingDots.textContent = 'Signing in' + '.'.repeat(dots);
    }, 300);

    // Restore button state after animation
    setTimeout(() => {
        clearInterval(dotAnimation);
        button.classList.remove('btn-loading', 'btn-pulse');
        button.textContent = originalText;
    }, 2000);
});

// Add subtle hover effect to form inputs
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('focus', function () {
        this.style.transition = 'all 0.3s ease';
        this.style.boxShadow = '0 0 10px rgba(0,123,255,0.2)';
    });

    input.addEventListener('blur', function () {
        this.style.boxShadow = '';
    });
});
