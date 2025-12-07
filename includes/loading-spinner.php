<?php
/**
 * Loading Spinner Component
 * Include this file and add the class 'loading-spinner-container' to any container
 * that should show a loading spinner during form submissions or page loads.
 */
?>
<div id="loadingSpinner" class="loading-spinner-container" style="display: none;">
    <div class="spinner">
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
    </div>
</div>

<style>
.loading-spinner-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner {
    --uib-size: 80px;
    --uib-color: #1a73e8;
    --uib-speed: 1s;
    --uib-stroke: 5px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: var(--uib-size);
    width: var(--uib-size);
}

.line {
    position: absolute;
    top: 0;
    left: calc(50% - var(--uib-stroke) / 2);
    display: flex;
    align-items: flex-start;
    height: 100%;
    width: var(--uib-stroke);
}

.line::before {
    content: '';
    height: 22%;
    width: 100%;
    border-radius: calc(var(--uib-stroke) / 2);
    background-color: var(--uib-color);
    animation: pulse calc(var(--uib-speed)) ease-in-out infinite;
    transition: background-color 0.3s ease;
    transform-origin: center bottom;
}

.line:nth-child(1) { transform: rotate(calc(360deg / -12 * 1)); }
.line:nth-child(1)::before { animation-delay: calc(var(--uib-speed) / -12 * 1); }
.line:nth-child(2) { transform: rotate(calc(360deg / -12 * 2)); }
.line:nth-child(2)::before { animation-delay: calc(var(--uib-speed) / -12 * 2); }
.line:nth-child(3) { transform: rotate(calc(360deg / -12 * 3)); }
.line:nth-child(3)::before { animation-delay: calc(var(--uib-speed) / -12 * 3); }
.line:nth-child(4) { transform: rotate(calc(360deg / -12 * 4)); }
.line:nth-child(4)::before { animation-delay: calc(var(--uib-speed) / -12 * 4); }
.line:nth-child(5) { transform: rotate(calc(360deg / -12 * 5)); }
.line:nth-child(5)::before { animation-delay: calc(var(--uib-speed) / -12 * 5); }
.line:nth-child(6) { transform: rotate(calc(360deg / -12 * 6)); }
.line:nth-child(6)::before { animation-delay: calc(var(--uib-speed) / -12 * 6); }
.line:nth-child(7) { transform: rotate(calc(360deg / -12 * 7)); }
.line:nth-child(7)::before { animation-delay: calc(var(--uib-speed) / -12 * 7); }
.line:nth-child(8) { transform: rotate(calc(360deg / -12 * 8)); }
.line:nth-child(8)::before { animation-delay: calc(var(--uib-speed) / -12 * 8); }
.line:nth-child(9) { transform: rotate(calc(360deg / -12 * 9)); }
.line:nth-child(9)::before { animation-delay: calc(var(--uib-speed) / -12 * 9); }
.line:nth-child(10) { transform: rotate(calc(360deg / -12 * 10)); }
.line:nth-child(10)::before { animation-delay: calc(var(--uib-speed) / -12 * 10); }
.line:nth-child(11) { transform: rotate(calc(360deg / -12 * 11)); }
.line:nth-child(11)::before { animation-delay: calc(var(--uib-speed) / -12 * 11); }

@keyframes pulse {
    0%, 80%, 100% {
        transform: scaleY(0.75);
        opacity: 0;
    }
    20% {
        transform: scaleY(1);
        opacity: 1;
    }
}
</style>

<script>
// Show loading spinner
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'flex';
    }
}

// Hide loading spinner
function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// Add loading spinner to all forms
document.addEventListener('DOMContentLoaded', function() {
    // Show spinner on form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });

    // Show spinner on link clicks that navigate away
    const links = document.querySelectorAll('a:not([target="_blank"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't show spinner for links that don't navigate away
            if (this.getAttribute('href') && 
                !this.getAttribute('href').startsWith('#') && 
                !this.classList.contains('no-loading')) {
                showLoading();
            }
        });
    });
});

// Hide spinner when page is fully loaded
window.addEventListener('load', function() {
    hideLoading();
});

// Handle back/forward navigation
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        hideLoading();
    }
});
</script>
