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

            // Also support direct images inserted via WYSIWYG editor inside .prose
            const proseImg = e.target.closest('.prose img');
            if (proseImg && !proseImg.closest('.lightbox-trigger') && !proseImg.closest('.rich-editor')) {
                e.preventDefault();
                const proseRoot = proseImg.closest('.prose');
                const imgs = proseRoot ? Array.from(proseRoot.querySelectorAll('img')) : [proseImg];
                const groupItems = imgs.map(function (img) {
                    const fig = img.closest('figure');
                    const capEl = fig ? fig.querySelector('figcaption') : null;
                    return {
                        src: img.getAttribute('src') || '',
                        caption: (capEl ? capEl.textContent.trim() : '') || img.getAttribute('alt') || ''
                    };
                }).filter(function (it) { return it.src !== ''; });
                const idx = Math.max(0, imgs.indexOf(proseImg));
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

    // 4. Social Share Buttons (Copy Link + Instagram / Native Mobile Web Share API)
    function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                ta.remove();
            }
        });
    }

    document.querySelectorAll('[data-share-bar]').forEach(function (bar) {
        const shareUrl = bar.getAttribute('data-share-url') || window.location.href;
        const shareTitle = bar.getAttribute('data-share-title') || document.title;
        const feedback = bar.querySelector('[data-share-feedback]');
        const copyBtn = bar.querySelector('[data-share-copy]');
        const copyLabel = bar.querySelector('[data-share-copy-label]');
        const igBtn = bar.querySelector('[data-share-instagram]');

        let fbTimer = null;
        function showFeedback(msg) {
            if (!feedback) return;
            feedback.textContent = msg;
            if (fbTimer) clearTimeout(fbTimer);
            fbTimer = setTimeout(function () { feedback.textContent = ''; }, 3200);
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                copyTextToClipboard(shareUrl).then(function () {
                    copyBtn.classList.add('is-copied');
                    if (copyLabel) copyLabel.textContent = 'Kopyalandı ✓';
                    showFeedback('Bağlantı panoya kopyalandı.');
                    setTimeout(function () {
                        copyBtn.classList.remove('is-copied');
                        if (copyLabel) copyLabel.textContent = 'Kopyala';
                    }, 2200);
                });
            });
        }

        if (igBtn) {
            igBtn.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({
                        title: shareTitle,
                        text: shareTitle,
                        url: shareUrl
                    }).catch(function () {
                        // User cancelled share sheet
                    });
                    return;
                }
                copyTextToClipboard(shareUrl).then(function () {
                    showFeedback('Bağlantı kopyalandı — Instagram hikaye veya mesajınıza yapıştırabilirsiniz.');
                });
            });
        }
    });

    // 5. Lightweight Page Visit Beacon (works accurately even with LiteSpeed Cache HIT)
    const body = document.body;
    const trackType = body ? body.getAttribute('data-track-type') : null;
    if (trackType) {
        const payload = JSON.stringify({
            path: window.location.pathname || '/',
            type: trackType,
            id: parseInt(body.getAttribute('data-track-id') || '0', 10) || 0,
            title: body.getAttribute('data-track-title') || document.title
        });
        if (navigator.sendBeacon) {
            try {
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('/api/track', blob);
            } catch (_) {
                // Fallback ignored
            }
        } else {
            fetch('/api/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function () {});
        }
    }

    // 6. Post & Project Like Button (Cache-safe live count + IP toggle)
    document.querySelectorAll('[data-like-widget]').forEach(function (widget) {
        const type = widget.getAttribute('data-like-type') || '';
        const id = parseInt(widget.getAttribute('data-like-id') || '0', 10) || 0;
        const btn = widget.querySelector('[data-like-btn]');
        const label = widget.querySelector('[data-like-label]');
        const countEl = widget.querySelector('[data-like-count]');
        if (!type || !id || !btn) return;

        function applyState(liked, count) {
            btn.classList.toggle('is-liked', !!liked);
            btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
            if (label) label.textContent = liked ? 'Beğendin' : 'Beğen';
            if (countEl) countEl.textContent = String(count || 0);
        }

        // Load fresh live status (never stale from LiteSpeed page cache)
        fetch('/api/likes?type=' + encodeURIComponent(type) + '&id=' + id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.ok) {
                    applyState(data.liked, data.count);
                }
            })
            .catch(function () {});

        let busy = false;
        btn.addEventListener('click', function () {
            if (busy) return;
            busy = true;
            fetch('/api/like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ type: type, id: id })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        applyState(data.liked, data.count);
                    }
                })
                .catch(function () {})
                .finally(function () { busy = false; });
        });
    });
})();
