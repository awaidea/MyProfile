<?php
require_once __DIR__ . '/config.php';

// Handle POST (save settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $s = [];

    // A. Theme
    $s['theme_mode'] = in_array($_POST['theme_mode'] ?? '', ['light','dark','auto'])
        ? $_POST['theme_mode'] : 'dark';
    $s['accent_color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent_color'] ?? '')
        ? $_POST['accent_color'] : '#8b9dc3';
    $s['overlay_opacity'] = (string) max(0, min(80, intval($_POST['overlay_opacity'] ?? 30)));

    // B. Background / Animation
    $s['bg_url'] = filter_var($_POST['bg_url'] ?? '', FILTER_SANITIZE_URL) ?: 'https://t.alcy.cc/moez';
    $s['bg_blur'] = (string) max(0, min(20, intval($_POST['bg_blur'] ?? 0)));
    $s['bg_brightness'] = (string) max(50, min(150, intval($_POST['bg_brightness'] ?? 100)));
    $s['typewriter_enabled'] = isset($_POST['typewriter_enabled']) ? '1' : '0';
    $s['typewriter_speed'] = (string) max(20, min(300, intval($_POST['typewriter_speed'] ?? 100)));

    // C. Profile
    $s['nickname'] = htmlspecialchars(trim($_POST['nickname'] ?? 'EdgeCat'), ENT_QUOTES, 'UTF-8');
    $s['avatar_url'] = filter_var($_POST['avatar_url'] ?? '', FILTER_SANITIZE_URL) ?: getDefaults()['avatar_url'];
    $s['subtitle_texts'] = htmlspecialchars($_POST['subtitle_texts'] ?? '', ENT_QUOTES, 'UTF-8');
    $s['gender'] = htmlspecialchars(trim($_POST['gender'] ?? ''), ENT_QUOTES, 'UTF-8');
    $s['birthday'] = htmlspecialchars(trim($_POST['birthday'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Social
    foreach (['github','qq','email','telegram','netease'] as $k) {
        $s['social_'.$k] = filter_var($_POST['social_'.$k] ?? '', FILTER_SANITIZE_URL) ?: '';
    }

    // D. Visibility
    foreach (['gender','birthday','age','sitemap','friends','footer',
              'social_github','social_qq','social_email','social_telegram','social_netease'] as $k) {
        $s['show_'.$k] = isset($_POST['show_'.$k]) ? '1' : '0';
    }

    saveSettings($s);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?saved=1');
    exit;
}

// Read settings
$S = getAllSettings();
$d = getDefaults();
function g(array $S, array $d, string $key) { return $S[$key] ?? $d[$key] ?? ''; }

$themeMode       = g($S,$d,'theme_mode');
$accentColor     = g($S,$d,'accent_color');
$overlayOpacity  = g($S,$d,'overlay_opacity');
$bgUrl           = g($S,$d,'bg_url');
$bgBlur          = g($S,$d,'bg_blur');
$bgBrightness    = g($S,$d,'bg_brightness');
$typeEnabled     = g($S,$d,'typewriter_enabled') === '1';
$typeSpeed       = g($S,$d,'typewriter_speed');
$nickname        = g($S,$d,'nickname');
$avatarUrl       = g($S,$d,'avatar_url');
$subtitleTexts   = g($S,$d,'subtitle_texts');
$gender          = g($S,$d,'gender');
$birthday        = g($S,$d,'birthday');

$sGithub    = g($S,$d,'social_github');
$sQq        = g($S,$d,'social_qq');
$sEmail     = g($S,$d,'social_email');
$sTelegram  = g($S,$d,'social_telegram');
$sNetease   = g($S,$d,'social_netease');

$vGender    = g($S,$d,'show_gender') === '1';
$vBirthday  = g($S,$d,'show_birthday') === '1';
$vAge       = g($S,$d,'show_age') === '1';
$vSitemap   = g($S,$d,'show_sitemap') === '1';
$vFriends   = g($S,$d,'show_friends') === '1';
$vFooter    = g($S,$d,'show_footer') === '1';
$vSGithub   = g($S,$d,'show_social_github') === '1';
$vSQq       = g($S,$d,'show_social_qq') === '1';
$vSEmail    = g($S,$d,'show_social_email') === '1';
$vSTelegram = g($S,$d,'show_social_telegram') === '1';
$vSNetease  = g($S,$d,'show_social_netease') === '1';

$savedMsg = isset($_GET['saved']) ? 'Settings saved!' : '';

// Age calc
$ageStr = '';
if ($birthday) {
    try {
        $bd = new DateTime($birthday);
        $ageStr = (string) $bd->diff(new DateTime())->y;
    } catch (Exception $e) {}
}

$bodyClass = $themeMode === 'light' ? 'theme-light' : ($themeMode === 'auto' ? 'theme-auto' : '');

// CSRF
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nickname) ?> - MyProfile</title>
    <link rel="icon" href="favicon.ico">
    <link rel="stylesheet" href="https://fontsapi.zeoseven.com/292/main/result.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/qexo-friends/friends.css">
    <link rel="stylesheet" href="index.css">
    <style>
        :root {
            --accent: <?= $accentColor ?>;
            --overlay-opacity: <?= $overlayOpacity / 100 ?>;
            --bg-blur: <?= $bgBlur ?>px;
            --bg-brightness: <?= $bgBrightness / 100 ?>;
        }
    </style>
</head>
<body class="<?= $bodyClass ?>">

<!-- Background -->
<div class="background" style="background-image:url('<?= htmlspecialchars($bgUrl) ?>')"></div>
<div class="overlay"></div>

<!-- Main -->
<div class="main-content">
    <div class="avatar-container">
        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="avatar" id="avatar">
        <h1 class="avatar-title"><?= htmlspecialchars($nickname) ?></h1>
    </div>

    <div class="subtitle-container">
        <span class="subtitle" id="subtitle"></span>
        <span class="cursor-container" id="cursor"><i class="fas fa-leaf"></i></span>
    </div>

    <?php if ($vGender || $vAge || $vBirthday): ?>
    <div class="tags-container">
        <?php if ($vGender): ?>
        <span class="tag"><i class="fas fa-mars"></i> <?= htmlspecialchars($gender) ?></span>
        <?php endif; ?>
        <?php if ($vAge && $ageStr): ?>
        <span class="tag"><i class="fas fa-birthday-cake"></i> <?= $ageStr ?>岁</span>
        <?php endif; ?>
        <?php if ($vBirthday): ?>
        <span class="tag"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($birthday) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($vSitemap || $vFriends): ?>
    <div class="function-buttons">
        <?php if ($vSitemap): ?>
        <button class="sitemap-button" id="sitemap-btn"><i class="fas fa-map"></i> 小地图</button>
        <?php endif; ?>
        <?php if ($vFriends): ?>
        <button class="friends-button" id="friends-btn"><i class="fas fa-heart"></i> 友人帐</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="social-icons">
        <?php if ($vSGithub && $sGithub): ?>
        <a href="<?= htmlspecialchars($sGithub) ?>" target="_blank" rel="noopener" class="social-icon" title="GitHub"><i class="fab fa-github"></i></a>
        <?php endif; ?>
        <?php if ($vSQq && $sQq): ?>
        <a href="<?= htmlspecialchars($sQq) ?>" target="_blank" rel="noopener" class="social-icon" title="QQ"><i class="fab fa-qq"></i></a>
        <?php endif; ?>
        <?php if ($vSEmail && $sEmail): ?>
        <a href="<?= htmlspecialchars($sEmail) ?>" class="social-icon" title="Email"><i class="fas fa-envelope"></i></a>
        <?php endif; ?>
        <?php if ($vSTelegram && $sTelegram): ?>
        <a href="<?= htmlspecialchars($sTelegram) ?>" target="_blank" rel="noopener" class="social-icon" title="Telegram"><i class="fab fa-telegram-plane"></i></a>
        <?php endif; ?>
        <?php if ($vSNetease && $sNetease): ?>
        <a href="<?= htmlspecialchars($sNetease) ?>" target="_blank" rel="noopener" class="social-icon" title="网易云音乐"><i class="fas fa-music"></i></a>
        <?php endif; ?>
    </div>
</div>

<!-- Settings Gear -->
<button class="settings-toggle" id="settings-toggle" title="Settings"><i class="fas fa-cog"></i></button>

<!-- Settings Panel -->
<div class="settings-overlay" id="settings-overlay"></div>
<div class="settings-panel" id="settings-panel">
    <div class="settings-header">
        <h2><i class="fas fa-sliders-h"></i> Settings</h2>
        <button class="settings-close" id="settings-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="settings-body">
        <form id="settings-form" method="POST" action="">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <!-- A: Theme -->
            <div class="settings-section">
                <button type="button" class="section-header" data-accordion>
                    <span><i class="fas fa-palette"></i> Theme &amp; Colors</span>
                    <i class="fas fa-chevron-down accordion-arrow"></i>
                </button>
                <div class="section-content">
                    <div class="form-group">
                        <label>Theme Mode</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="theme_mode" value="light" <?= $themeMode==='light'?'checked':'' ?>> Light</label>
                            <label class="radio-label"><input type="radio" name="theme_mode" value="dark" <?= $themeMode==='dark'?'checked':'' ?>> Dark</label>
                            <label class="radio-label"><input type="radio" name="theme_mode" value="auto" <?= $themeMode==='auto'?'checked':'' ?>> System</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="accent_color">Accent Color</label>
                        <div class="color-picker-wrap">
                            <input type="color" id="accent_color" name="accent_color" value="<?= $accentColor ?>">
                            <span class="color-val" id="accent-val"><?= $accentColor ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="overlay_opacity">Overlay Opacity: <span class="rv" id="ov"><?= $overlayOpacity ?>%</span></label>
                        <input type="range" id="overlay_opacity" name="overlay_opacity" min="0" max="80" value="<?= $overlayOpacity ?>">
                    </div>
                </div>
            </div>

            <!-- B: Background -->
            <div class="settings-section">
                <button type="button" class="section-header" data-accordion>
                    <span><i class="fas fa-image"></i> Background &amp; Animation</span>
                    <i class="fas fa-chevron-down accordion-arrow"></i>
                </button>
                <div class="section-content">
                    <div class="form-group">
                        <label for="bg_url">Background Image URL</label>
                        <input type="url" id="bg_url" name="bg_url" value="<?= htmlspecialchars($bgUrl) ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="bg_blur">Blur: <span class="rv" id="bv"><?= $bgBlur ?>px</span></label>
                        <input type="range" id="bg_blur" name="bg_blur" min="0" max="20" value="<?= $bgBlur ?>">
                    </div>
                    <div class="form-group">
                        <label for="bg_brightness">Brightness: <span class="rv" id="brv"><?= $bgBrightness ?>%</span></label>
                        <input type="range" id="bg_brightness" name="bg_brightness" min="50" max="150" value="<?= $bgBrightness ?>">
                    </div>
                    <div class="form-group toggle-group">
                        <label>Typewriter</label>
                        <label class="switch">
                            <input type="checkbox" name="typewriter_enabled" <?= $typeEnabled?'checked':'' ?>>
                            <span class="slider-track"></span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="typewriter_speed">Speed: <span class="rv" id="tsv"><?= $typeSpeed ?>ms</span></label>
                        <input type="range" id="typewriter_speed" name="typewriter_speed" min="20" max="300" value="<?= $typeSpeed ?>">
                    </div>
                </div>
            </div>

            <!-- C: Profile -->
            <div class="settings-section">
                <button type="button" class="section-header" data-accordion>
                    <span><i class="fas fa-user"></i> Profile</span>
                    <i class="fas fa-chevron-down accordion-arrow"></i>
                </button>
                <div class="section-content">
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname" value="<?= htmlspecialchars($nickname) ?>">
                    </div>
                    <div class="form-group">
                        <label for="avatar_url">Avatar URL</label>
                        <input type="url" id="avatar_url" name="avatar_url" value="<?= htmlspecialchars($avatarUrl) ?>">
                    </div>
                    <div class="form-group">
                        <label for="subtitle_texts">Subtitle Lines <small>(one per line)</small></label>
                        <textarea id="subtitle_texts" name="subtitle_texts" rows="4"><?= $subtitleTexts ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <input type="text" id="gender" name="gender" value="<?= htmlspecialchars($gender) ?>">
                        </div>
                        <div class="form-group">
                            <label for="birthday">Birthday</label>
                            <input type="date" id="birthday" name="birthday" value="<?= htmlspecialchars($birthday) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social -->
            <div class="settings-section">
                <button type="button" class="section-header" data-accordion>
                    <span><i class="fas fa-share-alt"></i> Social Links</span>
                    <i class="fas fa-chevron-down accordion-arrow"></i>
                </button>
                <div class="section-content">
                    <div class="form-group"><label for="sg"><i class="fab fa-github"></i> GitHub</label><input type="url" id="sg" name="social_github" value="<?= htmlspecialchars($sGithub) ?>"></div>
                    <div class="form-group"><label for="sq"><i class="fab fa-qq"></i> QQ</label><input type="url" id="sq" name="social_qq" value="<?= htmlspecialchars($sQq) ?>"></div>
                    <div class="form-group"><label for="se"><i class="fas fa-envelope"></i> Email</label><input type="url" id="se" name="social_email" value="<?= htmlspecialchars($sEmail) ?>"></div>
                    <div class="form-group"><label for="st"><i class="fab fa-telegram-plane"></i> Telegram</label><input type="url" id="st" name="social_telegram" value="<?= htmlspecialchars($sTelegram) ?>"></div>
                    <div class="form-group"><label for="sn"><i class="fas fa-music"></i> NetEase</label><input type="url" id="sn" name="social_netease" value="<?= htmlspecialchars($sNetease) ?>"></div>
                </div>
            </div>

            <!-- D: Visibility -->
            <div class="settings-section">
                <button type="button" class="section-header" data-accordion>
                    <span><i class="fas fa-eye"></i> Visibility</span>
                    <i class="fas fa-chevron-down accordion-arrow"></i>
                </button>
                <div class="section-content">
                    <p class="sub-label">Tags</p>
                    <div class="form-group toggle-group"><label>Gender Tag</label><label class="switch"><input type="checkbox" name="show_gender" <?= $vGender?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>Age Tag</label><label class="switch"><input type="checkbox" name="show_age" <?= $vAge?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>Birthday Tag</label><label class="switch"><input type="checkbox" name="show_birthday" <?= $vBirthday?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <p class="sub-label">Buttons</p>
                    <div class="form-group toggle-group"><label>Sitemap</label><label class="switch"><input type="checkbox" name="show_sitemap" <?= $vSitemap?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>Friends</label><label class="switch"><input type="checkbox" name="show_friends" <?= $vFriends?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <p class="sub-label">Social Icons</p>
                    <div class="form-group toggle-group"><label>GitHub</label><label class="switch"><input type="checkbox" name="show_social_github" <?= $vSGithub?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>QQ</label><label class="switch"><input type="checkbox" name="show_social_qq" <?= $vSQq?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>Email</label><label class="switch"><input type="checkbox" name="show_social_email" <?= $vSEmail?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>Telegram</label><label class="switch"><input type="checkbox" name="show_social_telegram" <?= $vSTelegram?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <div class="form-group toggle-group"><label>NetEase</label><label class="switch"><input type="checkbox" name="show_social_netease" <?= $vSNetease?'checked':'' ?>><span class="slider-track"></span></label></div>
                    <p class="sub-label">Other</p>
                    <div class="form-group toggle-group"><label>Footer</label><label class="switch"><input type="checkbox" name="show_footer" <?= $vFooter?'checked':'' ?>><span class="slider-track"></span></label></div>
                </div>
            </div>

            <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<?php if ($vFooter): ?>
<footer class="footer">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($nickname) ?> | Powered by MyProfile PHP</p>
</footer>
<?php endif; ?>

<!-- Sitemap Modal -->
<div class="modal" id="sitemap-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-map"></i> Sitemap</h3>
            <button class="modal-close" id="close-sitemap"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="sitemap-content">
            <p>Site map content here.</p>
        </div>
    </div>
</div>

<!-- Friends Modal -->
<div class="modal" id="friends-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-heart"></i> Friends</h3>
            <button class="modal-close" id="close-friends"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="qexo-friends"></div>
    </div>
</div>

<!-- Toast -->
<?php if ($savedMsg): ?>
<div class="toast show"><?= $savedMsg ?></div>
<?php endif; ?>

<script src="https://unpkg.com/qexo-static/1.6.0/files/hexo/friends.js"></script>
<script>
(function() {
    'use strict';

    var CFG = {
        enabled: <?= $typeEnabled ? 'true' : 'false' ?>,
        speed: <?= intval($typeSpeed) ?>,
        texts: <?= json_encode(array_values(array_filter(array_map('trim',
            explode("\n", html_entity_decode($subtitleTexts, ENT_QUOTES, 'UTF-8'))))), JSON_UNESCAPED_UNICODE) ?>
    };

    /* ---- Typewriter ---- */
    function Typewriter(el, cursor) {
        this.el = el; this.cursor = cursor;
        this.lines = CFG.texts; this.li = 0; this.ci = 0; this.del = false;
        this.base = CFG.speed;
    }
    Typewriter.prototype.tick = function() {
        var self = this;
        if (!self.lines.length) { self.el.textContent = ''; return; }
        var line = self.lines[self.li], dt;
        if (!self.del) {
            self.ci++;
            self.el.textContent = line.substring(0, self.ci);
            if (self.ci >= line.length) {
                self.del = true;
                setTimeout(function(){ self.tick(); }, 2000);
                return;
            }
            dt = self.base + Math.random() * self.base * 0.5;
        } else {
            self.ci--;
            self.el.textContent = line.substring(0, self.ci);
            if (self.ci <= 0) {
                self.del = false;
                self.li = (self.li + 1) % self.lines.length;
            }
            dt = self.base * 0.4;
        }
        setTimeout(function(){ self.tick(); }, dt);
    };

    var subEl = document.getElementById('subtitle');
    var curEl = document.getElementById('cursor');
    if (subEl) {
        if (CFG.enabled) {
            new Typewriter(subEl, curEl).tick();
            if (curEl) {
                curEl.style.animation = 'blink .7s infinite';
                var sty = document.createElement('style');
                sty.textContent = '@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}';
                document.head.appendChild(sty);
            }
        } else {
            subEl.textContent = CFG.texts[0] || '';
        }
    }

    /* ---- Settings Panel ---- */
    var togBtn = document.getElementById('settings-toggle');
    var panel  = document.getElementById('settings-panel');
    var overlay = document.getElementById('settings-overlay');
    var closeBtn = document.getElementById('settings-close');

    function openPanel()  { panel.classList.add('open'); overlay.classList.add('open'); }
    function closePanel() { panel.classList.remove('open'); overlay.classList.remove('open'); }

    if (togBtn) togBtn.onclick = openPanel;
    if (closeBtn) closeBtn.onclick = closePanel;
    if (overlay) overlay.onclick = closePanel;

    /* ---- Accordion ---- */
    document.querySelectorAll('[data-accordion]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var sec = btn.closest('.settings-section');
            var content = sec.querySelector('.section-content');
            var arrow = btn.querySelector('.accordion-arrow');
            var isOpen = sec.classList.contains('active');
            // close all
            document.querySelectorAll('.settings-section').forEach(function(s) {
                s.classList.remove('active');
                var c = s.querySelector('.section-content');
                if (c) c.style.maxHeight = null;
            });
            if (!isOpen) {
                sec.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 40 + 'px';
            }
        });
    });
    // open first section
    var first = document.querySelector('.settings-section');
    if (first) first.querySelector('[data-accordion]').click();

    /* ---- Theme live preview ---- */
    document.querySelectorAll('input[name="theme_mode"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.body.classList.remove('theme-light','theme-dark','theme-auto');
            if (this.value === 'light') document.body.classList.add('theme-light');
            else if (this.value === 'auto') document.body.classList.add('theme-auto');
        });
    });

    /* ---- Accent color preview ---- */
    var cp = document.getElementById('accent_color');
    if (cp) cp.addEventListener('input', function() {
        document.documentElement.style.setProperty('--accent', this.value);
        var v = document.getElementById('accent-val');
        if (v) v.textContent = this.value;
    });

    /* ---- Range sliders ---- */
    var ranges = [
        ['overlay_opacity','ov','%'],
        ['bg_blur','bv','px'],
        ['bg_brightness','brv','%'],
        ['typewriter_speed','tsv','ms']
    ];
    ranges.forEach(function(r) {
        var el = document.getElementById(r[0]);
        if (!el) return;
        el.addEventListener('input', function() {
            var d = document.getElementById(r[1]);
            if (d) d.textContent = this.value + r[2];
        });
    });

    /* ---- Toast ---- */
    var toast = document.querySelector('.toast');
    if (toast) setTimeout(function(){ toast.style.opacity = '0'; setTimeout(function(){ toast.remove(); }, 400); }, 3000);

    /* ---- Modals ---- */
    function setupModal(btnId, modalId, closeId) {
        var btn = document.getElementById(btnId);
        var modal = document.getElementById(modalId);
        var close = document.getElementById(closeId);
        if (btn) btn.onclick = function() { if (modal) modal.classList.add('open'); };
        if (close) close.onclick = function() { if (modal) modal.classList.remove('open'); };
        if (modal) modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.classList.remove('open');
        });
    }
    setupModal('sitemap-btn','sitemap-modal','close-sitemap');
    setupModal('friends-btn','friends-modal','close-friends');

    /* ---- Form submit via fetch ---- */
    var form = document.getElementById('settings-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(form);
            fetch(window.location.pathname, { method: 'POST', body: fd })
                .then(function() { window.location.href = window.location.pathname + '?saved=1'; })
                .catch(function(err) { alert('Save failed: ' + err.message); });
        });
    }
})();
</script>
</body>
</html>
