(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        return;
    }

    document.documentElement.classList.add('js-anim');

    /* 1. Hairline Scroll Progress Bar + Sticky Nav Elevation + Subtle Hero Parallax */
    const nav = document.querySelector('.recipe-nav');
    const hero = document.querySelector('.bali-hero');
    let progressBar = null;

    if (nav) {
        progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress-bar';
        progressBar.setAttribute('aria-hidden', 'true');
        document.body.prepend(progressBar);
    }

    let scrollTicking = false;

    function updateOnScroll() {
        scrollTicking = false;
        const scrollY = window.scrollY || window.pageYOffset || 0;

        if (nav) {
            nav.classList.toggle('is-scrolled', scrollY > 12);
        }

        if (progressBar) {
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const ratio = docHeight > 0 ? Math.min(1, Math.max(0, scrollY / docHeight)) : 0;
            progressBar.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
        }

        if (hero && window.innerWidth > 900) {
            const offset = Math.min(28, Math.round(scrollY * 0.14));
            hero.style.setProperty('--hero-parallax-y', offset + 'px');
        }
    }

    window.addEventListener(
        'scroll',
        function () {
            if (!scrollTicking) {
                scrollTicking = true;
                window.requestAnimationFrame(updateOnScroll);
            }
        },
        { passive: true }
    );
    updateOnScroll();

    /* 2. Scroll-Triggered Staggered Editorial Reveals via IntersectionObserver */
    const revealGroups = [
        { selector: '.section-heading-row', stagger: 0 },
        { selector: '.home-popular-card', stagger: 70 },
        { selector: '.home-cta-banner', stagger: 0 },
        { selector: '.recipe-toolbar', stagger: 0 },
        { selector: '.recipe-card', stagger: 55 },
        { selector: '.popular-sidebar', stagger: 80 },
        { selector: '.detail-sample-image', stagger: 0, mode: 'scale' },
        { selector: '.detail-info-col', stagger: 80 },
        { selector: '.detail-video-box', stagger: 60, mode: 'scale' },
        { selector: '.detail-panel', stagger: 75 },
        { selector: '.terms-card, .share-temple-art, .share-form-card', stagger: 70 },
        { selector: '.story-card', stagger: 70 },
        { selector: '.account-sidebar, .account-profile-card, .account-widget, .account-ulasan-panel', stagger: 65 },
        { selector: '.admin-stat-card', stagger: 60 },
        { selector: '.admin-card', stagger: 80 },
    ];

    const revealElements = [];

    revealGroups.forEach(function (group) {
        const nodes = document.querySelectorAll(group.selector);
        nodes.forEach(function (el, idx) {
            if (!el.hasAttribute('data-reveal')) {
                el.setAttribute('data-reveal', group.mode || 'up');
            }
            const delay = Math.min(idx * group.stagger, 280);
            el.style.setProperty('--reveal-delay', delay + 'ms');
            revealElements.push(el);
        });
    });

    if ('IntersectionObserver' in window && revealElements.length > 0) {
        const observer = new IntersectionObserver(
            function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.06,
                rootMargin: '0px 0px -24px 0px',
            }
        );

        revealElements.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        revealElements.forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    /* 3. Heart Favorite Button Spring Pop */
    document.addEventListener('click', function (event) {
        const favBtn = event.target.closest('.js-favorite-toggle');
        if (!favBtn) {
            return;
        }
        favBtn.classList.remove('just-toggled');
        void favBtn.offsetWidth;
        favBtn.classList.add('just-toggled');
    });

    /* 4. Rating Stars Cascade Hover Preview */
    const ratingGroup = document.getElementById('rating-stars');
    if (ratingGroup) {
        const starBtns = ratingGroup.querySelectorAll('.rating-star-btn');
        starBtns.forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                const hoverVal = Number(btn.getAttribute('data-value'));
                starBtns.forEach(function (item) {
                    const v = Number(item.getAttribute('data-value'));
                    item.classList.toggle('is-preview', v <= hoverVal);
                });
            });
        });

        ratingGroup.addEventListener('mouseleave', function () {
            starBtns.forEach(function (item) {
                item.classList.remove('is-preview');
            });
        });
    }

    /* 5. Smooth Entrance for Dynamically Added Recipe Steps */
    const stepsBuilder = document.getElementById('steps-builder');
    if (stepsBuilder && 'MutationObserver' in window) {
        const stepObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1 && node.classList.contains('step-row')) {
                        node.classList.add('is-entering');
                    }
                });
            });
        });
        stepObserver.observe(stepsBuilder, { childList: true });
    }

    /* 6. Subtle Count-Up Animation for Numeric Admin Stat Cards */
    document.querySelectorAll('.admin-stat-card strong').forEach(function (el) {
        const raw = el.textContent.trim();
        if (!/^\d+$/.test(raw)) {
            return;
        }
        const target = parseInt(raw, 10);
        const duration = 560;
        const start = performance.now();

        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(1, elapsed / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = String(Math.round(target * eased));
            if (progress < 1) {
                window.requestAnimationFrame(tick);
            }
        }

        window.requestAnimationFrame(tick);
    });
})();
