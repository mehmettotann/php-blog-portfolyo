/* =========================================================================
   example.com — Yönetim Paneli Etkileşimleri
   - Blok Tabanlı İçerik Düzenleyici (Metin, Görsel, Yerel Video, YouTube/Vimeo)
   - Proje Çoklu Medya Yöneticisi (Galeri + Yerel & Harici Videolar)
   - AJAX Dosya Yükleyici (İlerleme Çubuklu) & Medya Kütüphanesi Seçici
   ========================================================================= */

(function () {
    'use strict';

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function parseVideoUrl(url) {
        const clean = String(url || '').trim();
        if (!clean) return null;

        const yt = clean.match(/(?:https?:\/\/)?(?:www\.|m\.)?(?:youtube\.com\/(?:watch\?(?:[^&\s]*&)*v=|embed\/|shorts\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
        if (yt) {
            return {
                provider: 'youtube',
                video_id: yt[1],
                embed_url: 'https://www.youtube-nocookie.com/embed/' + yt[1] + '?rel=0',
                label: 'YouTube (' + yt[1] + ')'
            };
        }

        const vm = clean.match(/(?:https?:\/\/)?(?:www\.|player\.)?vimeo\.com\/(?:video\/|channels\/[a-zA-Z0-9_-]+\/|groups\/[a-zA-Z0-9_-]+\/videos\/)?(\d{6,12})/i);
        if (vm) {
            return {
                provider: 'vimeo',
                video_id: vm[1],
                embed_url: 'https://player.vimeo.com/video/' + vm[1] + '?dnt=1',
                label: 'Vimeo (' + vm[1] + ')'
            };
        }
        return null;
    }

    // AJAX Upload with real-time progress bar
    function uploadFileAjax(file, type, onProgress) {
        return new Promise(function (resolve, reject) {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('type', type);
            formData.append('file', file);

            xhr.open('POST', '/admin/api/upload', true);
            xhr.setRequestHeader('X-CSRF-Token', csrfToken);

            xhr.upload.addEventListener('progress', function (ev) {
                if (ev.lengthComputable && typeof onProgress === 'function') {
                    const pct = Math.round((ev.loaded / ev.total) * 100);
                    onProgress(pct);
                }
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                let json = null;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch (_) {
                    reject(new Error('Sunucu geçersiz yanıt döndürdü (dosya boyutu php.ini limitini aşmış olabilir).'));
                    return;
                }
                if (xhr.status >= 200 && xhr.status < 300 && json && json.ok) {
                    resolve(json.file);
                } else {
                    reject(new Error((json && json.error) || 'Yükleme başarısız.'));
                }
            };

            xhr.onerror = function () {
                reject(new Error('Ağ hatası nedeniyle dosya yüklenemedi.'));
            };

            xhr.send(formData);
        });
    }

    // Mobile Admin Sidebar Toggle
    const adminMenuBtn = document.getElementById('admin-menu-toggle');
    const adminSidebar = document.getElementById('admin-sidebar');
    if (adminMenuBtn && adminSidebar) {
        adminMenuBtn.addEventListener('click', function () {
            adminSidebar.classList.toggle('is-open');
        });
    }

    // =========================================================================
    // Media Library Picker Modal
    // =========================================================================
    const pickerModal = document.getElementById('media-picker-modal');
    const pickerTitle = document.getElementById('media-picker-title');
    const pickerGrid = document.getElementById('media-picker-grid');
    const pickerClose = document.getElementById('media-picker-close');
    let pickerCallback = null;

    function openMediaPicker(type, onSelect) {
        if (!pickerModal || !pickerGrid) return;
        pickerCallback = onSelect;
        if (pickerTitle) {
            pickerTitle.textContent = type === 'video' ? 'Sunucudan Video Seç' : 'Sunucudan Görsel Seç';
        }
        pickerGrid.innerHTML = '<p class="muted-text">Medya dosyaları yükleniyor...</p>';
        pickerModal.hidden = false;

        fetch('/admin/api/media?type=' + encodeURIComponent(type), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok || !Array.isArray(data.items) || data.items.length === 0) {
                    pickerGrid.innerHTML = '<p class="muted-text">Bu türde yüklenmiş dosya bulunamadı. Önce yeni bir dosya yükleyebilirsiniz.</p>';
                    return;
                }
                pickerGrid.innerHTML = '';
                data.items.forEach(function (item) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'picker-item';
                    btn.innerHTML =
                        '<div class="picker-item-thumb">' +
                        (item.file_type === 'image'
                            ? '<img src="' + esc(item.file_path) + '" alt="' + esc(item.original_name) + '" loading="lazy">'
                            : '<span>▶ VIDEO</span>') +
                        '</div>' +
                        '<div class="picker-item-meta" title="' + esc(item.original_name) + '">' +
                        esc(item.original_name) + ' · ' + esc(item.size_label || '') +
                        '</div>';
                    btn.addEventListener('click', function () {
                        const cb = pickerCallback;
                        closeMediaPicker();
                        if (typeof cb === 'function') cb(item.file_path, item);
                    });
                    pickerGrid.appendChild(btn);
                });
            })
            .catch(function () {
                pickerGrid.innerHTML = '<p class="muted-text">Medya listesi yüklenemedi.</p>';
            });
    }

    function closeMediaPicker() {
        if (pickerModal) pickerModal.hidden = true;
        pickerCallback = null;
    }

    if (pickerClose) pickerClose.addEventListener('click', closeMediaPicker);
    if (pickerModal) {
        pickerModal.addEventListener('click', function (e) {
            if (e.target === pickerModal) closeMediaPicker();
        });
    }

    // Cover image quick upload & library picker
    document.querySelectorAll('[data-ajax-cover]').forEach(function (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = fileInput.files && fileInput.files[0];
            if (!file) return;
            const targetInput = document.getElementById(fileInput.getAttribute('data-ajax-cover'));
            const previewBox = document.getElementById(fileInput.getAttribute('data-ajax-preview'));
            if (previewBox) previewBox.innerHTML = '<span class="muted-text">Yükleniyor...</span>';

            uploadFileAjax(file, 'image', function (pct) {
                if (previewBox) previewBox.innerHTML = '<span class="muted-text">Yükleniyor %' + pct + '</span>';
            })
                .then(function (uploaded) {
                    if (targetInput) targetInput.value = uploaded.path;
                    if (previewBox) previewBox.innerHTML = '<img src="' + esc(uploaded.path) + '" alt="Kapak">';
                    fileInput.value = '';
                })
                .catch(function (err) {
                    alert(err.message);
                    if (previewBox) previewBox.innerHTML = '<span class="muted-text">Yükleme başarısız</span>';
                });
        });
    });

    document.querySelectorAll('[data-pick-media]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const type = btn.getAttribute('data-pick-media') || 'image';
            const targetInput = document.getElementById(btn.getAttribute('data-pick-target'));
            const previewBox = document.getElementById(btn.getAttribute('data-pick-preview'));
            openMediaPicker(type, function (path) {
                if (targetInput) targetInput.value = path;
                if (previewBox && type === 'image') {
                    previewBox.innerHTML = '<img src="' + esc(path) + '" alt="Kapak">';
                }
            });
        });
    });

    // =========================================================================
    // Block-Based Content Editor (Blog Posts & Project Stories)
    // =========================================================================
    const blockLabels = {
        paragraph: 'Paragraf',
        heading: 'Alt Başlık',
        image: 'Görsel',
        video_local: 'Kendi Videom (MP4 / WebM)',
        video_external: 'Harici Video (YouTube / Vimeo)',
        quote: 'Alıntı',
        code: 'Kod Bloğu',
        list: 'Liste'
    };

    document.querySelectorAll('[data-block-editor]').forEach(function (editorEl) {
        const inputId = editorEl.getAttribute('data-target-input');
        const hiddenInput = document.getElementById(inputId);
        const listEl = editorEl.querySelector('[data-blocks-list]');
        if (!hiddenInput || !listEl) return;

        let blocks = [];
        try {
            const parsed = JSON.parse(hiddenInput.value || '[]');
            blocks = Array.isArray(parsed) ? parsed : [];
        } catch (_) {
            blocks = [];
        }

        function syncHidden() {
            hiddenInput.value = JSON.stringify(blocks);
        }

        function defaultBlock(type) {
            switch (type) {
                case 'heading': return { type: 'heading', level: 2, text: '' };
                case 'image': return { type: 'image', src: '', alt: '', caption: '' };
                case 'video_local': return { type: 'video_local', src: '', poster: '', title: '', caption: '' };
                case 'video_external': return { type: 'video_external', url: '', title: '', caption: '' };
                case 'quote': return { type: 'quote', text: '', cite: '' };
                case 'code': return { type: 'code', language: 'php', code: '' };
                case 'list': return { type: 'list', style: 'unordered', items: [] };
                default: return { type: 'paragraph', text: '' };
            }
        }

        function renderBlocks() {
            listEl.innerHTML = '';
            if (blocks.length === 0) {
                listEl.innerHTML = '<p class="muted-text small-note">Henüz içerik bloğu eklenmedi. Aşağıdaki butonlarla paragraf, görsel veya video ekleyin.</p>';
                syncHidden();
                return;
            }

            blocks.forEach(function (block, idx) {
                const card = document.createElement('div');
                card.className = 'block-item';

                const head = document.createElement('div');
                head.className = 'block-item-head';
                head.innerHTML =
                    '<span class="block-type-badge">' + esc((idx + 1) + '. ' + (blockLabels[block.type] || block.type)) + '</span>' +
                    '<div class="block-controls">' +
                    '<button type="button" class="btn btn-secondary btn-xs" data-move-up ' + (idx === 0 ? 'disabled' : '') + ' title="Yukarı taşı">↑</button>' +
                    '<button type="button" class="btn btn-secondary btn-xs" data-move-down ' + (idx === blocks.length - 1 ? 'disabled' : '') + ' title="Aşağı taşı">↓</button>' +
                    '<button type="button" class="btn btn-danger-outline btn-xs" data-remove-block title="Bloğu sil">Sil</button>' +
                    '</div>';

                head.querySelector('[data-move-up]').addEventListener('click', function () {
                    if (idx === 0) return;
                    const tmp = blocks[idx - 1];
                    blocks[idx - 1] = blocks[idx];
                    blocks[idx] = tmp;
                    renderBlocks();
                });

                head.querySelector('[data-move-down]').addEventListener('click', function () {
                    if (idx >= blocks.length - 1) return;
                    const tmp = blocks[idx + 1];
                    blocks[idx + 1] = blocks[idx];
                    blocks[idx] = tmp;
                    renderBlocks();
                });

                head.querySelector('[data-remove-block]').addEventListener('click', function () {
                    blocks.splice(idx, 1);
                    renderBlocks();
                });

                card.appendChild(head);

                const body = document.createElement('div');
                body.className = 'form-stack';

                if (block.type === 'paragraph') {
                    body.innerHTML =
                        '<textarea rows="4" placeholder="Paragraf metni... (**kalın**, *italik*, `kod`, [link](https://...) desteklenir)">' + esc(block.text || '') + '</textarea>';
                    body.querySelector('textarea').addEventListener('input', function (e) {
                        block.text = e.target.value;
                        syncHidden();
                    });
                } else if (block.type === 'heading') {
                    body.innerHTML =
                        '<div class="form-row-2">' +
                        '<select data-h-level><option value="2"' + ((block.level || 2) == 2 ? ' selected' : '') + '>H2 — Ana Bölüm Başlığı</option><option value="3"' + (block.level == 3 ? ' selected' : '') + '>H3 — Alt Başlık</option></select>' +
                        '<input type="text" data-h-text value="' + esc(block.text || '') + '" placeholder="Başlık metni...">' +
                        '</div>';
                    body.querySelector('[data-h-level]').addEventListener('change', function (e) {
                        block.level = parseInt(e.target.value, 10) || 2;
                        syncHidden();
                    });
                    body.querySelector('[data-h-text]').addEventListener('input', function (e) {
                        block.text = e.target.value;
                        syncHidden();
                    });
                } else if (block.type === 'image') {
                    body.innerHTML =
                        '<div class="inline-btn-row">' +
                        '<label class="btn btn-secondary btn-xs file-btn">Görsel Yükle<input type="file" accept="image/*" data-img-upload hidden></label>' +
                        '<button type="button" class="btn btn-secondary btn-xs" data-img-pick>Kütüphaneden Seç</button>' +
                        '<span class="row-meta" data-upload-status></span>' +
                        '</div>' +
                        '<input type="text" data-img-src value="' + esc(block.src || '') + '" placeholder="/uploads/images/..."> ' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-img-alt value="' + esc(block.alt || '') + '" placeholder="Alternatif metin (SEO alt)">' +
                        '<input type="text" data-img-cap value="' + esc(block.caption || '') + '" placeholder="Görsel açıklaması (isteğe bağlı)">' +
                        '</div>' +
                        '<div class="block-media-preview" data-img-preview>' + (block.src ? '<img src="' + esc(block.src) + '" alt="">' : '') + '</div>';

                    const srcIn = body.querySelector('[data-img-src]');
                    const preview = body.querySelector('[data-img-preview]');
                    const statusEl = body.querySelector('[data-upload-status]');

                    srcIn.addEventListener('input', function (e) {
                        block.src = e.target.value;
                        preview.innerHTML = block.src ? '<img src="' + esc(block.src) + '" alt="">' : '';
                        syncHidden();
                    });
                    body.querySelector('[data-img-alt]').addEventListener('input', function (e) { block.alt = e.target.value; syncHidden(); });
                    body.querySelector('[data-img-cap]').addEventListener('input', function (e) { block.caption = e.target.value; syncHidden(); });

                    body.querySelector('[data-img-upload]').addEventListener('change', function (e) {
                        const file = e.target.files && e.target.files[0];
                        if (!file) return;
                        statusEl.textContent = 'Yükleniyor...';
                        uploadFileAjax(file, 'image', function (pct) { statusEl.textContent = 'Yükleniyor %' + pct; })
                            .then(function (up) {
                                block.src = up.path;
                                srcIn.value = up.path;
                                preview.innerHTML = '<img src="' + esc(up.path) + '" alt="">';
                                statusEl.textContent = 'Yüklendi ✓';
                                syncHidden();
                            })
                            .catch(function (err) { statusEl.textContent = err.message; });
                    });

                    body.querySelector('[data-img-pick]').addEventListener('click', function () {
                        openMediaPicker('image', function (path) {
                            block.src = path;
                            srcIn.value = path;
                            preview.innerHTML = '<img src="' + esc(path) + '" alt="">';
                            syncHidden();
                        });
                    });
                } else if (block.type === 'video_local') {
                    body.innerHTML =
                        '<div class="inline-btn-row">' +
                        '<label class="btn btn-primary btn-xs file-btn">MP4 / WebM Yükle<input type="file" accept="video/mp4,video/webm" data-vid-upload hidden></label>' +
                        '<button type="button" class="btn btn-secondary btn-xs" data-vid-pick>Sunucudan Video Seç</button>' +
                        '<label class="btn btn-secondary btn-xs file-btn">Poster Görseli Yükle<input type="file" accept="image/*" data-poster-upload hidden></label>' +
                        '<button type="button" class="btn btn-secondary btn-xs" data-poster-pick>Kütüphaneden Poster</button>' +
                        '<span class="row-meta" data-upload-status></span>' +
                        '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-vid-src value="' + esc(block.src || '') + '" placeholder="Video yolu: /uploads/videos/..."> ' +
                        '<input type="text" data-vid-poster value="' + esc(block.poster || '') + '" placeholder="Kapak / Poster görseli (isteğe bağlı)">' +
                        '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-vid-title value="' + esc(block.title || '') + '" placeholder="Video başlığı (isteğe bağlı)">' +
                        '<input type="text" data-vid-cap value="' + esc(block.caption || '') + '" placeholder="Video açıklaması (isteğe bağlı)">' +
                        '</div>' +
                        '<div class="block-media-preview" data-vid-preview>' +
                        (block.src ? '<video controls playsinline preload="metadata"' + (block.poster ? ' poster="' + esc(block.poster) + '"' : '') + ' src="' + esc(block.src) + '"></video>' : '') +
                        '</div>';

                    const srcIn = body.querySelector('[data-vid-src]');
                    const posterIn = body.querySelector('[data-vid-poster]');
                    const preview = body.querySelector('[data-vid-preview]');
                    const statusEl = body.querySelector('[data-upload-status]');

                    function updateVidPreview() {
                        preview.innerHTML = block.src
                            ? '<video controls playsinline preload="metadata"' + (block.poster ? ' poster="' + esc(block.poster) + '"' : '') + ' src="' + esc(block.src) + '"></video>'
                            : '';
                    }

                    srcIn.addEventListener('input', function (e) { block.src = e.target.value; updateVidPreview(); syncHidden(); });
                    posterIn.addEventListener('input', function (e) { block.poster = e.target.value; updateVidPreview(); syncHidden(); });
                    body.querySelector('[data-vid-title]').addEventListener('input', function (e) { block.title = e.target.value; syncHidden(); });
                    body.querySelector('[data-vid-cap]').addEventListener('input', function (e) { block.caption = e.target.value; syncHidden(); });

                    body.querySelector('[data-vid-upload]').addEventListener('change', function (e) {
                        const file = e.target.files && e.target.files[0];
                        if (!file) return;
                        statusEl.textContent = 'Video yükleniyor...';
                        uploadFileAjax(file, 'video', function (pct) { statusEl.textContent = 'Video yükleniyor %' + pct; })
                            .then(function (up) {
                                block.src = up.path;
                                srcIn.value = up.path;
                                statusEl.textContent = 'Video yüklendi ✓';
                                updateVidPreview();
                                syncHidden();
                            })
                            .catch(function (err) { statusEl.textContent = err.message; });
                    });

                    body.querySelector('[data-vid-pick]').addEventListener('click', function () {
                        openMediaPicker('video', function (path) {
                            block.src = path;
                            srcIn.value = path;
                            updateVidPreview();
                            syncHidden();
                        });
                    });

                    body.querySelector('[data-poster-upload]').addEventListener('change', function (e) {
                        const file = e.target.files && e.target.files[0];
                        if (!file) return;
                        statusEl.textContent = 'Poster yükleniyor...';
                        uploadFileAjax(file, 'image', function (pct) { statusEl.textContent = 'Poster %' + pct; })
                            .then(function (up) {
                                block.poster = up.path;
                                posterIn.value = up.path;
                                statusEl.textContent = 'Poster yüklendi ✓';
                                updateVidPreview();
                                syncHidden();
                            })
                            .catch(function (err) { statusEl.textContent = err.message; });
                    });

                    body.querySelector('[data-poster-pick]').addEventListener('click', function () {
                        openMediaPicker('image', function (path) {
                            block.poster = path;
                            posterIn.value = path;
                            updateVidPreview();
                            syncHidden();
                        });
                    });
                } else if (block.type === 'video_external') {
                    const parsed = parseVideoUrl(block.url || '');
                    body.innerHTML =
                        '<input type="url" data-ext-url value="' + esc(block.url || '') + '" placeholder="YouTube veya Vimeo bağlantısını yapıştırın (örn: https://www.youtube.com/watch?v=...)">' +
                        '<div class="row-meta" data-ext-status>' + (parsed ? '✓ ' + esc(parsed.label) + ' otomatik algılandı' : 'Desteklenen kaynaklar: YouTube (watch, youtu.be, shorts, embed) ve Vimeo') + '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-ext-title value="' + esc(block.title || '') + '" placeholder="Video başlığı (isteğe bağlı)">' +
                        '<input type="text" data-ext-cap value="' + esc(block.caption || '') + '" placeholder="Açıklama (isteğe bağlı)">' +
                        '</div>' +
                        '<div class="block-media-preview" data-ext-preview>' +
                        (parsed ? '<div class="video-embed-16x9"><iframe src="' + esc(parsed.embed_url) + '" loading="lazy" allowfullscreen></iframe></div>' : '') +
                        '</div>';

                    const urlIn = body.querySelector('[data-ext-url]');
                    const statusEl = body.querySelector('[data-ext-status]');
                    const preview = body.querySelector('[data-ext-preview]');

                    urlIn.addEventListener('input', function (e) {
                        block.url = e.target.value;
                        const p = parseVideoUrl(block.url);
                        if (p) {
                            block.provider = p.provider;
                            block.video_id = p.video_id;
                            statusEl.textContent = '✓ ' + p.label + ' otomatik algılandı';
                            preview.innerHTML = '<div class="video-embed-16x9"><iframe src="' + esc(p.embed_url) + '" loading="lazy" allowfullscreen></iframe></div>';
                        } else {
                            statusEl.textContent = block.url.trim() ? 'Geçerli bir YouTube veya Vimeo URL\'si girin.' : '';
                            preview.innerHTML = '';
                        }
                        syncHidden();
                    });
                    body.querySelector('[data-ext-title]').addEventListener('input', function (e) { block.title = e.target.value; syncHidden(); });
                    body.querySelector('[data-ext-cap]').addEventListener('input', function (e) { block.caption = e.target.value; syncHidden(); });
                } else if (block.type === 'quote') {
                    body.innerHTML =
                        '<textarea rows="3" data-q-text placeholder="Alıntı metni...">' + esc(block.text || '') + '</textarea>' +
                        '<input type="text" data-q-cite value="' + esc(block.cite || '') + '" placeholder="Kaynak / Yazar (isteğe bağlı)">';
                    body.querySelector('[data-q-text]').addEventListener('input', function (e) { block.text = e.target.value; syncHidden(); });
                    body.querySelector('[data-q-cite]').addEventListener('input', function (e) { block.cite = e.target.value; syncHidden(); });
                } else if (block.type === 'code') {
                    body.innerHTML =
                        '<input type="text" data-c-lang value="' + esc(block.language || '') + '" placeholder="Dil (örn: php, sql, bash, javascript)">' +
                        '<textarea rows="5" style="font-family:var(--font-mono);font-size:0.86rem;" data-c-code placeholder="Kod bloğu...">' + esc(block.code || '') + '</textarea>';
                    body.querySelector('[data-c-lang]').addEventListener('input', function (e) { block.language = e.target.value; syncHidden(); });
                    body.querySelector('[data-c-code]').addEventListener('input', function (e) { block.code = e.target.value; syncHidden(); });
                } else if (block.type === 'list') {
                    const listText = Array.isArray(block.items) ? block.items.join('\n') : String(block.items || '');
                    body.innerHTML =
                        '<select data-l-style><option value="unordered"' + (block.style !== 'ordered' ? ' selected' : '') + '>Madde İşaretli Liste</option><option value="ordered"' + (block.style === 'ordered' ? ' selected' : '') + '>Numaralı Liste</option></select>' +
                        '<textarea rows="4" data-l-items placeholder="Her satıra bir liste maddesi yazın...">' + esc(listText) + '</textarea>';
                    body.querySelector('[data-l-style]').addEventListener('change', function (e) { block.style = e.target.value; syncHidden(); });
                    body.querySelector('[data-l-items]').addEventListener('input', function (e) {
                        block.items = e.target.value.split(/\r?\n/);
                        syncHidden();
                    });
                }

                card.appendChild(body);
                listEl.appendChild(card);
            });

            syncHidden();
        }

        editorEl.querySelectorAll('[data-add-block]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const type = btn.getAttribute('data-add-block') || 'paragraph';
                blocks.push(defaultBlock(type));
                renderBlocks();
            });
        });

        const parentForm = editorEl.closest('form');
        if (parentForm) {
            parentForm.addEventListener('submit', syncHidden);
        }

        renderBlocks();
    });

    // =========================================================================
    // Project Multi-Media Manager (Gallery Images + Local & External Videos)
    // =========================================================================
    document.querySelectorAll('[data-project-media-manager]').forEach(function (mgr) {
        const hiddenInput = document.getElementById(mgr.getAttribute('data-target-input'));
        const listEl = mgr.querySelector('[data-pm-list]');
        const progressBar = mgr.querySelector('[data-pm-progress]');
        const progressFill = mgr.querySelector('[data-pm-progress-fill]');
        const progressText = mgr.querySelector('[data-pm-progress-text]');
        if (!hiddenInput || !listEl) return;

        let items = [];
        try {
            const parsed = JSON.parse(hiddenInput.value || '[]');
            items = Array.isArray(parsed) ? parsed : [];
        } catch (_) {
            items = [];
        }

        function sync() {
            hiddenInput.value = JSON.stringify(items);
        }

        function setProgress(show, pct, text) {
            if (!progressBar) return;
            progressBar.hidden = !show;
            if (progressFill) progressFill.style.width = (pct || 0) + '%';
            if (progressText) progressText.textContent = text || '';
        }

        function renderMedia() {
            listEl.innerHTML = '';
            if (items.length === 0) {
                listEl.innerHTML = '<p class="muted-text small-note">Henüz galeri görseli veya proje videosu eklenmemiş. Yukarıdaki butonları kullanarak çoklu görsel, yerel MP4/WebM video veya YouTube/Vimeo videosu ekleyin.</p>';
                sync();
                return;
            }

            items.forEach(function (item, idx) {
                const card = document.createElement('div');
                card.className = 'pm-item';

                const typeLabel = item.media_type === 'image'
                    ? 'Galeri Görseli'
                    : (item.media_type === 'video_local' ? 'Yerel Video (MP4/WebM)' : 'Harici Video (YouTube/Vimeo)');

                const head = document.createElement('div');
                head.className = 'pm-item-head';
                head.innerHTML =
                    '<span class="block-type-badge">' + esc((idx + 1) + '. ' + typeLabel) + '</span>' +
                    '<div class="block-controls">' +
                    '<button type="button" class="btn btn-secondary btn-xs" data-pm-up ' + (idx === 0 ? 'disabled' : '') + '>↑</button>' +
                    '<button type="button" class="btn btn-secondary btn-xs" data-pm-down ' + (idx === items.length - 1 ? 'disabled' : '') + '>↓</button>' +
                    '<button type="button" class="btn btn-danger-outline btn-xs" data-pm-del>Sil</button>' +
                    '</div>';

                head.querySelector('[data-pm-up]').addEventListener('click', function () {
                    if (idx === 0) return;
                    const t = items[idx - 1]; items[idx - 1] = items[idx]; items[idx] = t;
                    renderMedia();
                });
                head.querySelector('[data-pm-down]').addEventListener('click', function () {
                    if (idx >= items.length - 1) return;
                    const t = items[idx + 1]; items[idx + 1] = items[idx]; items[idx] = t;
                    renderMedia();
                });
                head.querySelector('[data-pm-del]').addEventListener('click', function () {
                    items.splice(idx, 1);
                    renderMedia();
                });

                card.appendChild(head);

                const body = document.createElement('div');
                body.className = 'form-stack';

                if (item.media_type === 'image') {
                    body.innerHTML =
                        '<div class="block-media-preview"><img src="' + esc(item.file_path || '') + '" alt=""></div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-f="title" value="' + esc(item.title || '') + '" placeholder="Görsel başlığı (isteğe bağlı)">' +
                        '<input type="text" data-f="caption" value="' + esc(item.caption || '') + '" placeholder="Açıklama (isteğe bağlı)">' +
                        '</div>';
                } else if (item.media_type === 'video_local') {
                    body.innerHTML =
                        '<div class="block-media-preview"><video controls playsinline preload="metadata"' + (item.poster_path ? ' poster="' + esc(item.poster_path) + '"' : '') + ' src="' + esc(item.file_path || '') + '"></video></div>' +
                        '<div class="inline-btn-row">' +
                        '<label class="btn btn-secondary btn-xs file-btn">Poster Yükle<input type="file" accept="image/*" data-pm-poster-up hidden></label>' +
                        '<button type="button" class="btn btn-secondary btn-xs" data-pm-poster-pick>Kütüphaneden Poster</button>' +
                        '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-f="file_path" value="' + esc(item.file_path || '') + '" placeholder="/uploads/videos/..."> ' +
                        '<input type="text" data-f="poster_path" value="' + esc(item.poster_path || '') + '" placeholder="Poster görsel yolu (isteğe bağlı)">' +
                        '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-f="title" value="' + esc(item.title || '') + '" placeholder="Video başlığı">' +
                        '<input type="text" data-f="caption" value="' + esc(item.caption || '') + '" placeholder="Video açıklaması">' +
                        '</div>';

                    body.querySelector('[data-pm-poster-up]').addEventListener('change', function (e) {
                        const file = e.target.files && e.target.files[0];
                        if (!file) return;
                        setProgress(true, 10, 'Poster yükleniyor...');
                        uploadFileAjax(file, 'image', function (pct) { setProgress(true, pct, 'Poster yükleniyor %' + pct); })
                            .then(function (up) {
                                item.poster_path = up.path;
                                setProgress(false);
                                renderMedia();
                            })
                            .catch(function (err) { setProgress(false); alert(err.message); });
                    });

                    body.querySelector('[data-pm-poster-pick]').addEventListener('click', function () {
                        openMediaPicker('image', function (path) {
                            item.poster_path = path;
                            renderMedia();
                        });
                    });
                } else if (item.media_type === 'video_external') {
                    const p = parseVideoUrl(item.external_url || '');
                    body.innerHTML =
                        '<input type="url" data-f="external_url" value="' + esc(item.external_url || '') + '" placeholder="https://www.youtube.com/watch?v=... veya https://vimeo.com/..."> ' +
                        '<div class="row-meta" data-ext-info>' + (p ? '✓ ' + esc(p.label) : 'Geçerli YouTube veya Vimeo bağlantısı yapıştırın') + '</div>' +
                        '<div class="block-media-preview" data-ext-box>' +
                        (p ? '<div class="video-embed-16x9"><iframe src="' + esc(p.embed_url) + '" loading="lazy" allowfullscreen></iframe></div>' : '') +
                        '</div>' +
                        '<div class="form-row-2">' +
                        '<input type="text" data-f="title" value="' + esc(item.title || '') + '" placeholder="Video başlığı">' +
                        '<input type="text" data-f="caption" value="' + esc(item.caption || '') + '" placeholder="Video açıklaması">' +
                        '</div>';
                }

                body.querySelectorAll('[data-f]').forEach(function (inp) {
                    inp.addEventListener('input', function (e) {
                        const field = inp.getAttribute('data-f');
                        item[field] = e.target.value;
                        if (field === 'external_url') {
                            const p = parseVideoUrl(item.external_url);
                            const info = body.querySelector('[data-ext-info]');
                            const box = body.querySelector('[data-ext-box]');
                            if (p) {
                                item.provider = p.provider;
                                item.video_id = p.video_id;
                                if (info) info.textContent = '✓ ' + p.label;
                                if (box) box.innerHTML = '<div class="video-embed-16x9"><iframe src="' + esc(p.embed_url) + '" loading="lazy" allowfullscreen></iframe></div>';
                            } else {
                                if (info) info.textContent = 'Geçerli YouTube veya Vimeo bağlantısı yapıştırın';
                                if (box) box.innerHTML = '';
                            }
                        }
                        sync();
                    });
                });

                card.appendChild(body);
                listEl.appendChild(card);
            });

            sync();
        }

        // Multi-image upload
        const imgUp = mgr.querySelector('[data-pm-upload-images]');
        if (imgUp) {
            imgUp.addEventListener('change', async function () {
                const files = Array.from(imgUp.files || []);
                if (files.length === 0) return;
                for (let i = 0; i < files.length; i++) {
                    const f = files[i];
                    setProgress(true, 0, 'Görsel yükleniyor (' + (i + 1) + '/' + files.length + '): ' + f.name);
                    try {
                        const up = await uploadFileAjax(f, 'image', function (pct) {
                            setProgress(true, pct, 'Görsel yükleniyor (' + (i + 1) + '/' + files.length + ') %' + pct);
                        });
                        items.push({ media_type: 'image', file_path: up.path, title: '', caption: '' });
                        renderMedia();
                    } catch (err) {
                        alert(f.name + ': ' + err.message);
                    }
                }
                setProgress(false);
                imgUp.value = '';
            });
        }

        // Pick existing image
        const pickImgBtn = mgr.querySelector('[data-pm-pick-image]');
        if (pickImgBtn) {
            pickImgBtn.addEventListener('click', function () {
                openMediaPicker('image', function (path) {
                    items.push({ media_type: 'image', file_path: path, title: '', caption: '' });
                    renderMedia();
                });
            });
        }

        // Upload local MP4/WebM video
        const vidUp = mgr.querySelector('[data-pm-upload-video]');
        if (vidUp) {
            vidUp.addEventListener('change', function () {
                const file = vidUp.files && vidUp.files[0];
                if (!file) return;
                setProgress(true, 0, 'Video yükleniyor: ' + file.name);
                uploadFileAjax(file, 'video', function (pct) {
                    setProgress(true, pct, 'Video yükleniyor %' + pct + ' — ' + file.name);
                })
                    .then(function (up) {
                        items.push({ media_type: 'video_local', file_path: up.path, poster_path: '', title: '', caption: '' });
                        setProgress(false);
                        renderMedia();
                        vidUp.value = '';
                    })
                    .catch(function (err) {
                        setProgress(false);
                        alert(err.message);
                        vidUp.value = '';
                    });
            });
        }

        // Pick existing server video
        const pickVidBtn = mgr.querySelector('[data-pm-pick-video]');
        if (pickVidBtn) {
            pickVidBtn.addEventListener('click', function () {
                openMediaPicker('video', function (path) {
                    items.push({ media_type: 'video_local', file_path: path, poster_path: '', title: '', caption: '' });
                    renderMedia();
                });
            });
        }

        // Add external YouTube/Vimeo video
        const addExtBtn = mgr.querySelector('[data-pm-add-external]');
        if (addExtBtn) {
            addExtBtn.addEventListener('click', function () {
                items.push({ media_type: 'video_external', external_url: '', title: '', caption: '' });
                renderMedia();
            });
        }

        const parentForm = mgr.closest('form');
        if (parentForm) {
            parentForm.addEventListener('submit', sync);
        }

        renderMedia();
    });

    // =========================================================================
    // Categories Quick Edit
    // =========================================================================
    document.querySelectorAll('[data-edit-category]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('cat-id').value = btn.getAttribute('data-id') || '0';
            document.getElementById('cat-name').value = btn.getAttribute('data-name') || '';
            document.getElementById('cat-slug').value = btn.getAttribute('data-slug') || '';
            document.getElementById('cat-desc').value = btn.getAttribute('data-description') || '';
            document.getElementById('cat-form-title').textContent = 'Kategoriyi Düzenle';
            document.getElementById('cat-submit-btn').textContent = 'Güncelle';
            const resetBtn = document.getElementById('cat-reset-btn');
            if (resetBtn) resetBtn.hidden = false;
        });
    });

    const catResetBtn = document.getElementById('cat-reset-btn');
    if (catResetBtn) {
        catResetBtn.addEventListener('click', function () {
            document.getElementById('cat-id').value = '0';
            document.getElementById('cat-name').value = '';
            document.getElementById('cat-slug').value = '';
            document.getElementById('cat-desc').value = '';
            document.getElementById('cat-form-title').textContent = 'Yeni Kategori Ekle';
            document.getElementById('cat-submit-btn').textContent = 'Kategori Ekle';
            catResetBtn.hidden = true;
        });
    }

    // =========================================================================
    // Timeline Row Editor (Settings -> About)
    // =========================================================================
    const timelineWrap = document.getElementById('timeline-editor');
    const addTimelineBtn = document.getElementById('add-timeline-row');
    if (timelineWrap && addTimelineBtn) {
        addTimelineBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'timeline-edit-row';
            row.innerHTML =
                '<div class="form-row-3">' +
                '<input type="text" name="timeline_period[]" placeholder="Dönem (Örn: 2022 – 2024)">' +
                '<input type="text" name="timeline_role[]" placeholder="Rol / Bölüm">' +
                '<input type="text" name="timeline_place[]" placeholder="Kurum / Okul">' +
                '</div>' +
                '<div class="timeline-detail-line">' +
                '<input type="text" name="timeline_detail[]" placeholder="Kısa açıklama...">' +
                '<button type="button" class="btn btn-danger-outline btn-xs" data-remove-timeline>Sil</button>' +
                '</div>';
            timelineWrap.appendChild(row);
        });

        timelineWrap.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-remove-timeline]');
            if (btn) {
                const row = btn.closest('.timeline-edit-row');
                if (row) row.remove();
            }
        });
    }

    // =========================================================================
    // Copy Path Button in Media Library
    // =========================================================================
    document.querySelectorAll('[data-copy-path]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const val = btn.getAttribute('data-copy-path') || '';
            if (navigator.clipboard) {
                navigator.clipboard.writeText(val).then(function () {
                    const prev = btn.textContent;
                    btn.textContent = 'Kopyalandı ✓';
                    setTimeout(function () { btn.textContent = prev; }, 1400);
                });
            }
        });
    });
})();
