/**
 * IRECSTEM 2026 - Enhanced Animated Website JavaScript
 * Smooth scroll animations and interactive effects
 */

document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initBackToTop();
    initMobileMenu();
    initCounters();
    initScrollReveal();
    initSmoothScroll();
    initParallax();
    initTiltEffect();
    initCursorEffect();
});

/**
 * Navigation scroll effect with smooth transition
 */
function initNavigation() {
    const navbar = document.getElementById('navbar');

    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 80) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

/**
 * Mobile menu toggle with animation
 */
function initMobileMenu() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');

            // Animate hamburger to X
            const spans = this.querySelectorAll('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            }
        });

        // Close menu when clicking a link
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            });
        });
    }
}

/**
 * Back to top button with smooth scroll
 */
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');

    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 600) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Animated counters with Intersection Observer
 */
function initCounters() {
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');

    if (!statNumbers.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.getAttribute('data-target'));
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    statNumbers.forEach(stat => observer.observe(stat));
}

function animateCounter(element, target) {
    let current = 0;
    const increment = target / 60;
    const duration = 2000;
    const stepTime = duration / 60;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target + '+';
            element.style.transform = 'scale(1.1)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current) + '+';
        }
    }, stepTime);
}

/**
 * Scroll Reveal Animation
 */
function initScrollReveal() {
    const reveals = document.querySelectorAll('.card, .stat-card, .track-card, .schedule-card, .section-header');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Add staggered delay based on index
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) translateX(0)';
                }, index * 100);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    reveals.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(50px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });
}

/**
 * Smooth scroll for anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Parallax effect for background
 */
function initParallax() {
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const bgPattern = document.querySelector('.bg-pattern');

        if (bgPattern) {
            bgPattern.style.transform = `translateY(${scrolled * 0.3}px)`;
        }
    });
}

/**
 * 3D Tilt effect for cards
 */
function initTiltEffect() {
    const cards = document.querySelectorAll('.card, .track-card');

    cards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;

            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-12px)`;
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}

/**
 * Custom cursor effect
 */
function initCursorEffect() {
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    cursor.style.cssText = `
        position: fixed;
        width: 20px;
        height: 20px;
        border: 2px solid var(--accent);
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: transform 0.15s ease-out, width 0.2s, height 0.2s;
        transform: translate(-50%, -50%);
    `;
    document.body.appendChild(cursor);

    const cursorDot = document.createElement('div');
    cursorDot.className = 'cursor-dot';
    cursorDot.style.cssText = `
        position: fixed;
        width: 6px;
        height: 6px;
        background: var(--accent);
        border-radius: 50%;
        pointer-events: none;
        z-index: 10000;
        transform: translate(-50%, -50%);
    `;
    document.body.appendChild(cursorDot);

    document.addEventListener('mousemove', (e) => {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top = e.clientY + 'px';
        cursorDot.style.left = e.clientX + 'px';
        cursorDot.style.top = e.clientY + 'px';
    });

    // Scale cursor on interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .card, .track-card, .btn');
    interactiveElements.forEach(el => {
        el.addEventListener('mouseenter', () => {
            cursor.style.width = '40px';
            cursor.style.height = '40px';
            cursor.style.borderColor = 'var(--blue)';
        });
        el.addEventListener('mouseleave', () => {
            cursor.style.width = '20px';
            cursor.style.height = '20px';
            cursor.style.borderColor = 'var(--accent)';
        });
    });
}

/**
 * Track button clicks for analytics
 */
function trackAction(action) {
    console.log('Action tracked:', action);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:100px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#00a651' : type === 'error' ? '#dc3545' : '#0038A8';
    toast.style.cssText = `
        padding: 16px 24px;
        background: ${bgColor};
        color: white;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        animation: toastSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        gap: 12px;
    `;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes toastSlideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes toastSlideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }

    /* Hide custom cursor on touch devices */
    @media (hover: none) {
        .custom-cursor, .cursor-dot { display: none; }
    }
`;
document.head.appendChild(style);

// Initialize countdown
if (typeof updateCountdown === 'undefined') {
    function updateCountdown() {
        const daysEl = document.getElementById('days');
        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');

        if (!daysEl) return;

        const conferenceDate = new Date('September 15, 2026 09:00:00').getTime();

        function update() {
            const now = new Date().getTime();
            const distance = conferenceDate - now;

            if (distance < 0) {
                daysEl.textContent = '00';
                hoursEl.textContent = '00';
                minutesEl.textContent = '00';
                secondsEl.textContent = '00';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Animate number change
            [daysEl, hoursEl, minutesEl, secondsEl].forEach((el, i) => {
                const values = [days, hours, minutes, seconds];
                const newValue = values[i].toString().padStart(2, '0');
                if (el.textContent !== newValue) {
                    el.style.transform = 'scale(1.2)';
                    el.textContent = newValue;
                    setTimeout(() => {
                        el.style.transform = 'scale(1)';
                    }, 150);
                }
            });
        }

        update();
        setInterval(update, 1000);
    }

    // Start countdown when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateCountdown);
    } else {
        updateCountdown();
    }
}

console.log('IRECSTEM 2026 - Enhanced Animated Scripts Loaded');
