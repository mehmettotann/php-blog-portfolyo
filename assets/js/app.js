/* =========================================================================
   example.com — Public Interactive Scripts
   ========================================================================= */

(function () {
    'use strict';

    // 1. Light / Dark Theme Toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('bp-theme', next);
            } catch (_) {
                // Ignore storage errors in private mode
            }
        });
    }

    // 2. Mobile Navigation Drawer
    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            const open = mainNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // 3. Lightbox Modal for Project Galleries & Article Images
    const lightbox = document.getElementById('lightbox');
    const lbImg = document.getElementById('lightbox-img');
    const lbCap = document.getElementById('lightbox-caption');
    const lbPrev = document.getElementById('lightbox-prev');
    const lbNext = document.getElementById('lightbox-next');

    if (lightbox && lbImg && lbCap) {
        let items = [];
        let currentIndex = 0;

        function renderLightbox() {
            const item = items[currentIndex];
            if (!item) return;
            lbImg.src = item.src;
            lbImg.alt = item.caption || 'Görsel';
            lbCap.textContent = item.caption || '';
            const showNav = items.length > 1;
            if (lbPrev) lbPrev.style.display = showNav ? 'inline-flex' : 'none';
            if (lbNext) lbNext.style.display = showNav ? 'inline-flex' : 'none';
        }

        function openLightbox(groupItems, index) {
            items = groupItems;
            currentIndex = index;
            renderLightbox();
            lightbox.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.hidden = true;
            lbImg.src = '';
            document.body.style.overflow = '';
        }

        function step(delta) {
            if (items.length < 2) return;
            currentIndex = (currentIndex + delta + items.length) % items.length;
            renderLightbox();
        }

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('.lightbox-trigger');
            if (trigger) {
                e.preventDefault();
                const groupEl = trigger.closest('[data-lightbox-group]');
                const triggers = groupEl
                    ? Array.from(groupEl.querySelectorAll('.lightbox-trigger'))
                    : [trigger];

                const groupItems = triggers.map(function (el) {
                    return {
                        src: el.getAttribute('data-lightbox-src') || '',
                        caption: el.getAttribute('data-lightbox-caption') || ''
                    };
                }).filter(function (it) { return it.src !== ''; });

                const idx = Math.max(0, triggers.indexOf(trigger));
                if (groupItems.length > 0) {
                    openLightbox(groupItems, idx);
                }
                return;
            }

            if (e.target.closest('[data-lightbox-close]')) {
                closeLightbox();
            }
        });

        if (lbPrev) lbPrev.addEventListener('click', function () { step(-1); });
        if (lbNext) lbNext.addEventListener('click', function () { step(1); });

        document.addEventListener('keydown', function (e) {
            if (lightbox.hidden) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') step(-1);
            if (e.key === 'ArrowRight') step(1);
        });
    }
})();
