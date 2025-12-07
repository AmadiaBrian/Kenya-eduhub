document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.testimonials-track');
    const cards = document.querySelectorAll('.testimonial-card');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    let currentIndex = 0;
    let autoSlideInterval;
    const slideDuration = 5000; // 5 seconds per slide
    
    // Initialize the slider
    function initSlider() {
        if (cards.length === 0) return;
        
        // Show first testimonial
        updateActiveCard(0);
        
        // Start auto-sliding
        startAutoSlide();
        
        // Pause auto-slide on hover
        track.addEventListener('mouseenter', pauseAutoSlide);
        track.addEventListener('mouseleave', startAutoSlide);
        
        // Touch events for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            pauseAutoSlide();
        }, { passive: true });
        
        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoSlide();
        }, { passive: true });
    }
    
    // Handle swipe gestures
    function handleSwipe() {
        const swipeThreshold = 50; // Minimum distance to consider as a swipe
        
        if (touchStartX - touchEndX > swipeThreshold) {
            // Swipe left - go to next slide
            nextSlide();
        } else if (touchEndX - touchStartX > swipeThreshold) {
            // Swipe right - go to previous slide
            prevSlide();
        }
    }
    
    // Update active card and dots
    function updateActiveCard(index) {
        // Update active class on cards
        cards.forEach((card, i) => {
            if (i === index) {
                card.classList.add('active');
                // Reset progress bar animation
                const progressBar = card.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.animation = 'none';
                    void progressBar.offsetWidth; // Trigger reflow
                    progressBar.style.animation = `progress ${slideDuration}ms linear`;
                }
            } else {
                card.classList.remove('active');
            }
        });
        
        // Update active dot
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
        
        currentIndex = index;
    }
    
    // Go to next slide
    function nextSlide() {
        const nextIndex = (currentIndex + 1) % cards.length;
        updateActiveCard(nextIndex);
    }
    
    // Go to previous slide
    function prevSlide() {
        const prevIndex = (currentIndex - 1 + cards.length) % cards.length;
        updateActiveCard(prevIndex);
    }
    
    // Start auto-sliding
    function startAutoSlide() {
        if (autoSlideInterval) clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(nextSlide, slideDuration);
    }
    
    // Pause auto-sliding
    function pauseAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }
    }
    
    // Event listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            startAutoSlide();
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            startAutoSlide();
        });
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            updateActiveCard(index);
            startAutoSlide();
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') {
            nextSlide();
            startAutoSlide();
        } else if (e.key === 'ArrowLeft') {
            prevSlide();
            startAutoSlide();
        }
    });
    
    // Initialize the slider
    initSlider();
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Recalculate any responsive styles if needed
        }, 250);
    });
});
