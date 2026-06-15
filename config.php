<?php
/**
 * MyProfile Configuration
 * SQLite-based settings persistence
 */

/**
 * Get or create SQLite database connection
 */
function getDB(): PDO {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $dbPath = $dir . '/settings.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec('CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at INTEGER NOT NULL
    )');

    return $db;
}

/**
 * Read all settings as key => value pairs
 */
function getAllSettings(): array {
    $db = getDB();
    $stmt = $db->query('SELECT key, value FROM settings');
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}

/**
 * Get a single setting value (with default fallback)
 */
function getSetting(string $key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare('SELECT value FROM settings WHERE key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

/**
 * Save a single setting
 */
function saveSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO settings (key, value, updated_at) VALUES (?, ?, ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
    );
    $stmt->execute([$key, $value, time()]);
}

/**
 * Save multiple settings at once
 */
function saveSettings(array $settings): void {
    $db = getDB();
    $now = time();
    $stmt = $db->prepare(
        'INSERT INTO settings (key, value, updated_at) VALUES (?, ?, ?)
         ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at'
    );
    $db->beginTransaction();
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, (string)$value, $now]);
    }
    $db->commit();
}

/**
 * Default values for all settings
 */
function getDefaults(): array {
    return [
        // Theme
        'theme_mode' => 'dark',
        'accent_color' => '#8b9dc3',
        'overlay_opacity' => '30',

        // Background
        'bg_url' => 'https://t.alcy.cc/moez',
        'bg_blur' => '0',
        'bg_brightness' => '100',

        // Animation
        'typewriter_enabled' => '1',
        'typewriter_speed' => '100',

        // Profile
        'nickname' => 'EdgeCat',
        'avatar_url' => 'http://q.qlogo.cn/headimg_dl?dst_uin=2025650064&spec=640&img_type=jpg',
        'subtitle_texts' => "一只在代码世界里探索的喵\nEdgeCat | 开发者 | 猫奴\n热爱技术，热爱生活",
        'gender' => '男',
        'birthday' => '2000-01-01',

        // Social links
        'social_github' => 'https://github.com/awaidea',
        'social_qq' => 'https://wpa.qq.com/msgrd?v=3&uin=2025650064&site=qq&menu=yes',
        'social_email' => 'mailto:edge@catp.cc',
        'social_telegram' => 'https://t.me/edgecat',
        'social_netease' => 'https://music.163.com/#/user/home?id=0',

        // Visibility
        'show_gender' => '1',
        'show_birthday' => '1',
        'show_age' => '1',
        'show_sitemap' => '1',
        'show_friends' => '1',
        'show_social_github' => '1',
        'show_social_qq' => '1',
        'show_social_email' => '1',
        'show_social_telegram' => '1',
        'show_social_netease' => '1',
        'show_footer' => '1',
    ];
}
