// =====================================================================
// script.js
// Core utilities for the Exercise Tracker
// Dark mode, ripple effects, animated counters, confetti, fade-in,
// toast notifications, export/print
// =====================================================================

// ---------------------------------------------------------------
// Dark Mode Toggle
// ---------------------------------------------------------------
function initDarkMode() {
    const toggle = document.querySelector('.dark-mode-toggle');
    if (!toggle) return;

    // Load saved preference
    const saved = localStorage.getItem('darkMode');
    if (saved === 'true') {
        document.body.classList.add('dark-mode');
        toggle.textContent = '☀️';
    }

    toggle.addEventListener('click', function () {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        toggle.textContent = isDark ? '☀️' : '🌙';
    });
}

// ---------------------------------------------------------------
// Ripple Effect on Buttons
// ---------------------------------------------------------------
function initRippleEffect() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn');
        if (!btn) return;

        const ripple = document.createElement('span');
        ripple.classList.add('ripple');
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(ripple);

        setTimeout(function () {
            ripple.remove();
        }, 600);
    });
}

// ---------------------------------------------------------------
// Animated Number Counters
// ---------------------------------------------------------------
function animateCounter(element, target, duration) {
    duration = duration || 1500;
    var start = 0;
    var startTime = null;

    // Handle decimals
    var isDecimal = String(target).indexOf('.') !== -1;
    var decimals = isDecimal ? (String(target).split('.')[1] || '').length : 0;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        var progress = Math.min((timestamp - startTime) / duration, 1);
        // Ease out cubic
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = start + (target - start) * eased;

        if (isDecimal) {
            element.textContent = current.toFixed(decimals);
        } else {
            element.textContent = Math.floor(current);
        }

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            if (isDecimal) {
                element.textContent = parseFloat(target).toFixed(decimals);
            } else {
                element.textContent = target;
            }
        }
    }

    requestAnimationFrame(step);
}

function initCounters() {
    var counters = document.querySelectorAll('.counter[data-target]');
    if (counters.length === 0) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                entry.target.dataset.animated = 'true';
                var target = parseFloat(entry.target.dataset.target);
                animateCounter(entry.target, target, 1500);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(function (counter) {
        counter.textContent = '0';
        observer.observe(counter);
    });
}

// ---------------------------------------------------------------
// Fade-in on Scroll (IntersectionObserver)
// ---------------------------------------------------------------
function initFadeIn() {
    var elements = document.querySelectorAll('.fade-in');
    if (elements.length === 0) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

    elements.forEach(function (el) {
        observer.observe(el);
    });
}

// ---------------------------------------------------------------
// Confetti Animation
// ---------------------------------------------------------------
function launchConfetti(duration) {
    duration = duration || 3000;
    var canvas = document.createElement('canvas');
    canvas.classList.add('confetti-canvas');
    document.body.appendChild(canvas);

    var ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    var colors = ['#A78BFA', '#60A5FA', '#22D3EE', '#34D399', '#FB923C', '#FBBF24', '#F87171'];
    var particles = [];

    for (var i = 0; i < 150; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            w: Math.random() * 10 + 5,
            h: Math.random() * 6 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - 0.5) * 4,
            vy: Math.random() * 3 + 2,
            rotation: Math.random() * 360,
            rotSpeed: (Math.random() - 0.5) * 10,
            opacity: 1
        });
    }

    var startTime = Date.now();

    function animate() {
        var elapsed = Date.now() - startTime;
        if (elapsed > duration) {
            canvas.remove();
            return;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        var fadeStart = duration * 0.7;
        particles.forEach(function (p) {
            if (elapsed > fadeStart) {
                p.opacity = Math.max(0, 1 - (elapsed - fadeStart) / (duration - fadeStart));
            }

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.globalAlpha = p.opacity;
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();

            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.05;
            p.rotation += p.rotSpeed;
        });

        requestAnimationFrame(animate);
    }

    animate();
}

// ---------------------------------------------------------------
// Toast Notification System
// ---------------------------------------------------------------
function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 3000;

    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var icons = {
        success: '✅',
        error: '❌',
        info: 'ℹ️',
        warning: '⚠️'
    };

    var toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = (icons[type] || 'ℹ️') + ' ' + message;
    container.appendChild(toast);

    setTimeout(function () {
        toast.classList.add('toast-out');
        setTimeout(function () {
            toast.remove();
        }, 300);
    }, duration);
}

// ---------------------------------------------------------------
// Print / Export PDF
// ---------------------------------------------------------------
function exportReport() {
    window.print();
}

// ---------------------------------------------------------------
// Initialize everything on DOM ready
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initRippleEffect();
    initCounters();
    initFadeIn();
});
