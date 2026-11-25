<?php
use App\Helpers;

$editorInputName = $editorInputName ?? 'content_html';
$editorHtml = $editorHtml ?? '<p><br></p>';
?>
<div class="rich-editor" data-rich-editor>
    <div class="re-toolbar" role="toolbar" aria-label="Metin ve medya düzenleme araç çubuğu">
        <div class="re-group">
            <button type="button" class="re-btn" data-re-block="P" title="Normal Paragraf">P</button>
            <button type="button" class="re-btn" data-re-block="H2" title="Büyük Alt Başlık (H2)">H2</button>
            <button type="button" class="re-btn" data-re-block="H3" title="Küçük Alt Başlık (H3)">H3</button>
        </div>

        <div class="re-group">
            <button type="button" class="re-btn" data-re-cmd="bold" title="Kalın (Ctrl+B)"><strong>B</strong></button>
            <button type="button" class="re-btn" data-re-cmd="italic" title="İtalik (Ctrl+I)"><em>I</em></button>
            <button type="button" class="re-btn" data-re-cmd="underline" title="Altı Çizili"><u>U</u></button>
            <button type="button" class="re-btn" data-re-cmd="removeFormat" title="Biçimlendirmeyi Temizle">Tx</button>
        </div>

        <div class="re-group">
            <button type="button" class="re-btn" data-re-cmd="insertUnorderedList" title="Madde İşaretli Liste">• Liste</button>
            <button type="button" class="re-btn" data-re-cmd="insertOrderedList" title="Numaralı Liste">1. Liste</button>
            <button type="button" class="re-btn" data-re-quote title="Alıntı Ekle">“ Alıntı</button>
            <button type="button" class="re-btn" data-re-code title="Kod Bloğu Ekle">&lt;Kod&gt;</button>
            <button type="button" class="re-btn" data-re-link title="Bağlantı (Link) Ekle">🔗 Link</button>
            <button type="button" class="re-btn" data-re-cmd="insertHorizontalRule" title="Yatay Ayırıcı Çizgi">—</button>
        </div>

        <div class="re-group re-group-media">
            <label class="re-btn re-btn-accent file-btn" title="Bilgisayardan görsel yükleyip imlecin olduğu yere ekle">
                📷 Görsel Yükle
                <input type="file" accept="image/*" multiple data-re-upload-image hidden>
            </label>
            <button type="button" class="re-btn" data-re-pick-image title="Medya kütüphanesinden görsel ekle">🖼 Kütüphane</button>
            <label class="re-btn re-btn-accent file-btn" title="MP4 veya WebM video yükleyip ekle">
                🎬 Video Yükle
                <input type="file" accept="video/mp4,video/webm" data-re-upload-video hidden>
            </label>
            <button type="button" class="re-btn" data-re-pick-video title="Sunucudaki videolardan seç">📁 Sunucu Video</button>
            <button type="button" class="re-btn" data-re-ext-video title="YouTube veya Vimeo bağlantısı ekle">▶ YouTube / Vimeo</button>
        </div>

        <div class="re-group re-group-right">
            <button type="button" class="re-btn" data-re-toggle-source title="HTML kaynak görünümünü aç/kapat">&lt;/&gt; HTML</button>
        </div>
    </div>

    <div class="upload-progress-bar re-progress" data-re-progress hidden>
        <div class="upload-progress-fill" data-re-progress-fill></div>
        <span data-re-progress-text>Yükleniyor...</span>
    </div>

    <div class="re-surface prose" contenteditable="true" data-re-surface spellcheck="true" aria-label="İçerik editörü"><?= $editorHtml ?></div>

    <textarea class="re-source" data-re-source hidden aria-label="HTML kaynak kodu"></textarea>
    <textarea name="<?= Helpers::e($editorInputName) ?>" data-re-hidden hidden><?= Helpers::e($editorHtml) ?></textarea>

    <div class="re-footer">
        <span>İpucu: Normal metin gibi yazın; araç çubuğundan görsel/video ekleyin, ekran görüntüsünü doğrudan <strong>Ctrl+V</strong> ile yapıştırın veya görseli editöre sürükleyip bırakın.</span>
    </div>
</div>
