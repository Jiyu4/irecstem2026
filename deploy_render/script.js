/**
 * IRECSTEM 2026 - Conference Website JavaScript
 * Complete interactive functionality for all pages
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all modules
    initNavigation();
    initBackToTop();
    initForms();
    initToast();
    initCountdown();
    initModals();
    initProgramTabs();
    initCounters();
    initMobileMenu();
});

/**
 * Mobile menu functionality
 */
function initMobileMenu() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });

        // Close menu when clicking a link
        navMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function () {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
            });
        });
    }
}

/**
 * Navigation scroll effect
 */
function initNavigation() {
    const navbar = document.getElementById('navbar');

    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

/**
 * Back to top button
 */
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');

    if (backToTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 500) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * Countdown timer
 */
function initCountdown() {
    const daysEl = document.getElementById('days');
    const hoursEl = document.getElementById('hours');
    const minutesEl = document.getElementById('minutes');
    const secondsEl = document.getElementById('seconds');

    if (!daysEl) return;

    const conferenceDate = new Date('September 15, 2026 09:00:00').getTime();

    function updateCountdown() {
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

        daysEl.textContent = days.toString().padStart(2, '0');
        hoursEl.textContent = hours.toString().padStart(2, '0');
        minutesEl.textContent = minutes.toString().padStart(2, '0');
        secondsEl.textContent = seconds.toString().padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

/**
 * Animated counters
 */
function initCounters() {
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    statNumbers.forEach(stat => observer.observe(stat));
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;

    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            element.textContent = target + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}

/**
 * Program tabs functionality
 */
function initProgramTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const programDays = document.querySelectorAll('.program-day');

    if (!tabButtons.length) return;

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const day = this.getAttribute('data-day');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            programDays.forEach(dayEl => {
                dayEl.classList.remove('active');
                if (dayEl.id === 'day-' + day) {
                    dayEl.classList.add('active');
                }
            });
        });
    });
}

/**
 * Modal functionality
 */
function initModals() {
    // Registration buttons
    document.querySelectorAll('.register-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const modal = document.getElementById('registrationModal');
            const regType = this.getAttribute('data-type');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                // Set registration type in form
                const select = modal.querySelector('#regType');
                if (select) select.value = regType;
            }
        });
    });

    // Paper submission button
    const submitPaperBtn = document.getElementById('submitPaperBtn');
    if (submitPaperBtn) {
        submitPaperBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const modal = document.getElementById('paperModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    }

    // Download brochure button
    const downloadBrochureBtn = document.getElementById('downloadBrochureBtn');
    if (downloadBrochureBtn) {
        downloadBrochureBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showToast('Brochure download will be available soon!', 'info');
        });
    }

    // Close modal buttons
    document.querySelectorAll('.modal-close, .modal-overlay, .close-modal').forEach(el => {
        el.addEventListener('click', function () {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });
}

/**
 * Form handling
 */
function initForms() {
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (validateForm(this)) {
                const successModal = document.getElementById('successModal');
                if (successModal) {
                    successModal.classList.add('active');
                }
                this.reset();
            }
        });
    }

    // Newsletter form
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]');
            if (email.value && isValidEmail(email.value)) {
                showToast('Successfully subscribed to our newsletter!', 'success');
                this.reset();
            } else {
                showToast('Please enter a valid email address.', 'error');
            }
        });
    }

    // Paper submission form
    const paperForm = document.getElementById('paperForm');
    if (paperForm) {
        paperForm.addEventListener('submit', function (e) {
            // Allow real file upload if the form has multipart encoding
            const enc = (this.getAttribute('enctype') || '').toLowerCase();
            if (enc === 'multipart/form-data') {
                return; // do not block submission
            }

            // Otherwise keep existing front-end-only behavior (other pages / older modal)
            e.preventDefault();
            if (validateForm(this)) {
                showToast('Paper submitted successfully! You will receive confirmation via email.', 'success');
                this.reset();
                const modal = document.getElementById('paperModal');
                if (modal) modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }


    // Registration form
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (validateForm(this)) {
                showToast('Registration submitted! You will receive payment instructions via email.', 'success');
                this.reset();
                const modal = document.getElementById('registrationModal');
                if (modal) modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        const value = input.value.trim();

        if (!value) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        } else if (input.type === 'email' && !isValidEmail(value)) {
            isValid = false;
            input.style.borderColor = '#ef4444';
        } else {
            input.style.borderColor = '#e5e5e5';
        }
    });

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Toast notification system
 */
function initToast() {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    let icon = '';
    switch (type) {
        case 'success':
            icon = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            icon = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'info':
        default:
            icon = '<i class="fas fa-info-circle"></i>';
    }

    toast.innerHTML = `${icon}<span>${message}</span>`;
    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastSlideIn 0.3s ease-out reverse';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/* ========================================
   STATIC MODE - All motion effects removed
======================================== */

// All 3D card tilt, magnetic button, parallax scroll, particle effects,
// logo animations, and cursor-based motion have been disabled
// for a stable, static page experience

console.log('IRECSTEM 2026 - Website Scripts Loaded (Static Mode)');
