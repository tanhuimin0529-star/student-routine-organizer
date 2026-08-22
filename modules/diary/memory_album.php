<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_memory_album.php';
require_once __DIR__ . '/diary_navigation.php';

$delete_flash = isset($_SESSION['diary_delete_flash']) && is_array($_SESSION['diary_delete_flash'])
    ? $_SESSION['diary_delete_flash']
    : null;
unset($_SESSION['diary_delete_flash']);
$memory_album_context = diaryNavigationBuildReturnTo('memory_album.php');
$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

$album_photos = diaryMemoryAlbumExtractPhotos(
    $entries,
    $logged_in_user_id,
    true
);
$photos_by_month = diaryMemoryAlbumGroupPhotosByMonth($album_photos);
$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');

function memoryAlbumEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function memoryAlbumDisplayDate($entry_date) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $entry_date);

    return $date && $date->format('Y-m-d') === $entry_date
        ? $date->format('F j, Y')
        : (string) $entry_date;
}

function memoryAlbumMonthHeading($month_key) {
    $date = DateTimeImmutable::createFromFormat('!Y-m', (string) $month_key);

    return $date && $date->format('Y-m') === $month_key
        ? $date->format('F Y')
        : (string) $month_key;
}

function memoryAlbumMoodEmoji($mood) {
    $icons = array(
        'Happy' => '😊',
        'Calm' => '😌',
        'Neutral' => '😐',
        'Sad' => '😢',
        'Stressed' => '😣'
    );

    return isset($icons[$mood]) ? $icons[$mood] : '📝';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Album</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page diary-memory-album-page">
    <main class="diary-container diary-memory-album-container">
        <header class="diary-list-header diary-memory-album-hero">
            <div class="diary-title-group">
                <p class="diary-eyebrow">Personal Journal</p>
                <h1>Memory Album</h1>
                <p>A scrapbook of the photos already tucked inside your journal pages.</p>
            </div>
            <div class="diary-header-actions">
                <a class="diary-button diary-button-secondary" href="index.php">Back to Diary Home</a>
            </div>
        </header>

        <?php if ($delete_flash !== null): ?>
            <?php $delete_flash_type = isset($delete_flash['type']) && $delete_flash['type'] === 'success' ? 'success' : 'error'; ?>
            <div
                class="diary-alert diary-alert-<?php echo memoryAlbumEscape($delete_flash_type); ?>"
                data-diary-flash="<?php echo memoryAlbumEscape($delete_flash_type); ?>"
                role="<?php echo $delete_flash_type === 'success' ? 'status' : 'alert'; ?>"
            >
                <?php echo memoryAlbumEscape(isset($delete_flash['message']) ? $delete_flash['message'] : 'Journal entry could not be deleted right now. Please try again.'); ?>
            </div>
        <?php endif; ?>

        <?php if ($load_error): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                Your Memory Album could not be loaded right now. Please try again later.
            </div>
        <?php elseif (empty($photos_by_month)): ?>
            <section class="diary-memory-album-empty" aria-labelledby="memory-album-empty-heading">
                <span class="diary-memory-album-empty-icon" aria-hidden="true">📷</span>
                <h2 id="memory-album-empty-heading">Your album is waiting for its first memory.</h2>
                <p>Add photos to your journal to build your Memory Album.</p>
                <a class="diary-button diary-new-entry-button" href="add.php">+ New Journal Entry</a>
            </section>
        <?php else: ?>
            <?php $album_card_index = 0; ?>
            <div class="diary-memory-album-groups">
                <?php foreach ($photos_by_month as $month_key => $month_photos): ?>
                    <?php $month_heading_id = 'memory-album-month-' . str_replace('-', '', $month_key); ?>
                    <section class="diary-memory-album-month" aria-labelledby="<?php echo memoryAlbumEscape($month_heading_id); ?>">
                        <header class="diary-memory-album-month-heading">
                            <span class="diary-memory-album-month-tab" aria-hidden="true"></span>
                            <div>
                                <p class="diary-eyebrow">Photo Memories</p>
                                <h2 id="<?php echo memoryAlbumEscape($month_heading_id); ?>">
                                    <?php echo memoryAlbumEscape(memoryAlbumMonthHeading($month_key)); ?>
                                </h2>
                            </div>
                            <p><?php echo memoryAlbumEscape(count($month_photos)); ?> <?php echo count($month_photos) === 1 ? 'photo' : 'photos'; ?></p>
                        </header>

                        <div class="diary-memory-album-grid">
                            <?php foreach ($month_photos as $photo): ?>
                                <?php
                                $variation = ($album_card_index % 6) + 1;
                                $album_card_index++;
                                $photo_alt = $photo['caption'] !== ''
                                    ? $photo['caption']
                                    : 'Photo from ' . $photo['title'];
                                ?>
                                <article class="diary-memory-photo-card diary-memory-photo-card--<?php echo memoryAlbumEscape($variation); ?>">
                                    <span class="diary-memory-photo-tape" aria-hidden="true"></span>
                                    <div class="diary-memory-photo-frame">
                                        <img
                                            src="<?php echo memoryAlbumEscape($photo['image_path']); ?>"
                                            alt="<?php echo memoryAlbumEscape($photo_alt); ?>"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </div>

                                    <div class="diary-memory-photo-details">
                                        <div class="diary-memory-photo-meta">
                                            <time datetime="<?php echo memoryAlbumEscape($photo['entry_date']); ?>">
                                                <?php echo memoryAlbumEscape(memoryAlbumDisplayDate($photo['entry_date'])); ?>
                                            </time>
                                            <span>
                                                <span aria-hidden="true"><?php echo memoryAlbumEscape(memoryAlbumMoodEmoji($photo['mood'])); ?></span>
                                                <?php echo memoryAlbumEscape($photo['mood']); ?>
                                            </span>
                                        </div>
                                        <h3><?php echo memoryAlbumEscape($photo['title']); ?></h3>
                                        <?php if ($photo['caption'] !== ''): ?>
                                            <p class="diary-memory-photo-caption"><?php echo memoryAlbumEscape($photo['caption']); ?></p>
                                        <?php endif; ?>
                                        <a class="diary-action-button diary-action-primary" href="<?php echo memoryAlbumEscape(diaryNavigationViewUrl($photo['diary_id'], $memory_album_context)); ?>">
                                            View Journal
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

