/**
 * Advanced Design Enhancements
 * Scroll animations, ripple effects, and interactions
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // Scroll-triggered Animations
    // ============================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate-in');
                }, index * 100); // Stagger animation
            }
        });
    }, observerOptions);

    // Observe all form sections
    document.querySelectorAll('.form-section').forEach(section => {
        observer.observe(section);
    });

    // ============================================
    // Progress Indicator
    // ============================================
    function createProgressIndicator() {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'form-progress';
        progressContainer.innerHTML = '<div class="form-progress-bar"></div>';
        document.body.prepend(progressContainer);
        return progressContainer.querySelector('.form-progress-bar');
    }

    const progressBar = createProgressIndicator();

    function updateProgress() {
        const formContainer = document.querySelector('.form-container');
        if (!formContainer) return;

        const scrollTop = formContainer.scrollTop;
        const scrollHeight = formContainer.scrollHeight - formContainer.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;

        progressBar.style.width = scrollPercent + '%';
    }

    const formContainer = document.querySelector('.form-container');
    if (formContainer) {
        formContainer.addEventListener('scroll', updateProgress);
    }

    // ============================================
    // Button Ripple Effect
    // ============================================
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // Ripple is handled by CSS, but we can add extra effects here
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
        });
    });

    // ============================================
    // 3D Card Tilt Effect (subtle)
    // ============================================
    document.querySelectorAll('.form-section').forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 30;
            const rotateY = (centerX - x) / 30;

            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });

    // ============================================
    // Floating Labels Enhancement
    // ============================================
    document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
        // Add placeholder for floating label to work
        if (!input.hasAttribute('placeholder')) {
            input.setAttribute('placeholder', ' ');
        }

        // Focus/blur effects
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // ============================================
    // Animated Number Counter for Price
    // ============================================
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16); // 60fps
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = '£' + current.toFixed(2);
        }, 16);
    }

    // Watch for price changes
    const priceObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const priceElement = document.getElementById('totalPrice');
                if (priceElement) {
                    const newValue = parseFloat(priceElement.textContent.replace('£', ''));
                    if (!isNaN(newValue)) {
                        // Add pulse animation
                        priceElement.style.animation = 'none';
                        setTimeout(() => {
                            priceElement.style.animation = 'priceGlow 2s ease-in-out infinite';
                        }, 10);
                    }
                }
            }
        });
    });

    const priceElement = document.getElementById('totalPrice');
    if (priceElement) {
        priceObserver.observe(priceElement, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    // ============================================
    // Smooth Scroll to Errors
    // ============================================
    function scrollToError() {
        const firstError = document.querySelector('.error-message, .alert-danger');
        if (firstError) {
            firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            // Pulse effect
            firstError.style.animation = 'pulse 0.5s ease-in-out 3';
        }
    }

    // Check for errors on page load
    setTimeout(scrollToError, 500);

    // ============================================
    // Enhanced Checkbox Interactions
    // ============================================
    document.querySelectorAll('.checkbox-label input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Create confetti effect (subtle)
                createMiniConfetti(this);
            }
        });
    });

    function createMiniConfetti(element) {
        const rect = element.getBoundingClientRect();
        const colors = ['#eb008b', '#d40080', '#ff69b4'];

        for (let i = 0; i < 5; i++) {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                width: 4px;
                height: 4px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${rect.left + rect.width / 2}px;
                top: ${rect.top + rect.height / 2}px;
                border-radius: 50%;
                pointer-events: none;
                z-index: 10000;
                transition: all 0.6s ease-out;
            `;
            document.body.appendChild(confetti);

            setTimeout(() => {
                const angle = (Math.random() * 360) * Math.PI / 180;
                const distance = 30 + Math.random() * 30;
                confetti.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px)`;
                confetti.style.opacity = '0';
            }, 10);

            setTimeout(() => {
                confetti.remove();
            }, 700);
        }
    }

    // ============================================
    // Add Attendee Button Enhancement
    // ============================================
    const addAttendeeBtn = document.getElementById('add-attendee-btn');
    if (addAttendeeBtn) {
        addAttendeeBtn.addEventListener('click', function() {
            // Add scale animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);

            // Scroll to new attendee with delay
            setTimeout(() => {
                const attendees = document.querySelectorAll('.attendee-card');
                const lastAttendee = attendees[attendees.length - 1];
                if (lastAttendee) {
                    lastAttendee.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    // Highlight new attendee
                    lastAttendee.style.animation = 'pulse 0.5s ease-in-out';
                }
            }, 100);
        });
    }

    // ============================================
    // Form Submission Enhancement
    // ============================================
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    // ============================================
    // Parallax Effect on Sidebar
    // ============================================
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && formContainer) {
        formContainer.addEventListener('scroll', function() {
            const scrolled = this.scrollTop;
            sidebar.style.transform = `translateY(${scrolled * 0.1}px)`;
        });
    }

    // ============================================
    // Keyboard Accessibility Enhancements
    // ============================================
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const form = document.getElementById('booking-form');
            if (form) {
                form.requestSubmit();
            }
        }
    });

    // ============================================
    // Console Easter Egg
    // ============================================
    console.log('%c✨ ECHO 2026 ✨', 'font-size: 24px; font-weight: bold; background: linear-gradient(135deg, #eb008b, #d40080); -webkit-background-clip: text; color: transparent;');
    console.log('%cBuilt with ❤️ for Alive Church', 'font-size: 12px; color: #666;');
    console.log('%c🎨 Enhanced with modern design effects', 'font-size: 10px; color: #999; font-style: italic;');
});

// ============================================
// Pulse Animation Keyframes (injected)
// ============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
`;
document.head.appendChild(style);
