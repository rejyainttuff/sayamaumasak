(function () {
    'use strict';

    /* 0. Always-On Interactive Controls (Mobile Dashboard Drawer, Escape Key, Form Loading)
       Must run regardless of prefers-reduced-motion so navigation is never broken. */
    function closeAllMobileDrawers() {
        document.querySelectorAll('[data-mobile-drawer]').forEach(function (drawer) {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            const id = drawer.id;
            if (id) {
                const backdrop = document.querySelector('[data-drawer-backdrop="' + id + '"]');
                if (backdrop) {
                    backdrop.classList.remove('is-visible');
                }
                const trigger = document.querySelector('[data-drawer-open="' + id + '"]');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }
        });
        document.querySelectorAll('[data-drawer-backdrop].is-visible').forEach(function (bg) {
            bg.classList.remove('is-visible');
        });
        document.body.classList.remove('mobile-drawer-open');
        document.body.style.overflow = '';
    }

    function openMobileDrawer(id, triggerBtn) {
        const drawer = document.getElementById(id);
        const backdrop = document.querySelector('[data-drawer-backdrop="' + id + '"]');
        if (!drawer) {
            return;
        }
        const willOpen = !drawer.classList.contains('is-open');
        closeAllMobileDrawers();
        if (willOpen) {
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            if (backdrop) {
                backdrop.classList.add('is-visible');
            }
            if (triggerBtn) {
                triggerBtn.setAttribute('aria-expanded', 'true');
            }
            document.body.classList.add('mobile-drawer-open');
            document.body.style.overflow = 'hidden';
        }
    }

    document.querySelectorAll('[data-drawer-open]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const id = btn.getAttribute('data-drawer-open');
            if (id) {
                openMobileDrawer(id, btn);
            }
        });
    });

    document.querySelectorAll('[data-drawer-close]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeAllMobileDrawers();
        });
    });

    document.querySelectorAll('[data-drawer-backdrop]').forEach(function (bg) {
        bg.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeAllMobileDrawers();
        });
    });

    document.querySelectorAll('[data-mobile-drawer] a').forEach(function (link) {
        link.addEventListener('click', closeAllMobileDrawers);
    });

    window.addEventListener('pageshow', closeAllMobileDrawers);
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeAllMobileDrawers();
        }
    }, { passive: true });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllMobileDrawers();
            document.querySelectorAll('details[open]').forEach(function (d) {
                d.removeAttribute('open');
            });
        }
    });

    /* Form submit loading feedback (never fakes success before backend response) */
    document.querySelectorAll('form[data-loading-form], form[method="POST"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]:not([disabled])');
            if (btn && !btn.dataset.noLoading) {
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
            }
        });
    });

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
        { selector: '.story-card, .magazine-lead-story, .magazine-feed-item, .editorial-spotlight, .explore-featured-banner, .explore-recipe-item', stagger: 65 },
        { selector: '.account-profile-card, .account-widget, .account-ulasan-panel', stagger: 65 },
        { selector: '.admin-stat-card', stagger: 60 },
        { selector: '.admin-card', stagger: 80 },
    ];

    const revealSet = new Set();

    revealGroups.forEach(function (group) {
        const nodes = document.querySelectorAll(group.selector);
        nodes.forEach(function (el, idx) {
            if (el.hasAttribute('data-mobile-drawer')) {
                return;
            }
            if (!el.hasAttribute('data-reveal')) {
                el.setAttribute('data-reveal', group.mode || 'up');
            }
            const delay = Math.min(idx * group.stagger, 280);
            el.style.setProperty('--reveal-delay', delay + 'ms');
            revealSet.add(el);
        });
    });

    /* Ensure EVERY pre-existing [data-reveal] element in Blade is also observed and never stuck invisible */
    document.querySelectorAll('[data-reveal]').forEach(function (el) {
        if (!el.hasAttribute('data-mobile-drawer')) {
            revealSet.add(el);
        }
    });

    const revealElements = Array.from(revealSet);

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
                threshold: 0.01,
                rootMargin: '0px 0px 40px 0px',
            }
        );

        revealElements.forEach(function (el) {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight + 60 && rect.bottom > -40) {
                el.classList.add('is-visible');
            } else {
                observer.observe(el);
            }
        });

        /* Safety net: never leave any [data-reveal] section permanently hidden */
        window.setTimeout(function () {
            revealElements.forEach(function (el) {
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight + 120) {
                    el.classList.add('is-visible');
                }
            });
        }, 650);
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
