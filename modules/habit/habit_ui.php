<?php
/** Shared presentation helpers for the Habit Garden module. */

function habit_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function habit_set_flash(string $type, string $message): void
{
    $_SESSION['habit_flash'] = ['type' => $type, 'message' => $message];
}

function habit_take_flash(): ?array
{
    $flash = $_SESSION['habit_flash'] ?? null;
    unset($_SESSION['habit_flash']);

    return is_array($flash) ? $flash : null;
}

function habit_current_user_name(): string
{
    $name = $_SESSION['username'] ?? $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Friend';
    return trim((string) $name) !== '' ? trim((string) $name) : 'Friend';
}

function habit_render_head(string $title, string $activePage = ''): void
{
    $pages = [
        'home' => ['label' => 'Home', 'href' => 'index.php', 'icon' => 'bi-house-door'],
        'garden' => ['label' => 'Garden', 'href' => 'garden.php', 'icon' => 'bi-leaf'],
        'rewards' => ['label' => 'Rewards', 'href' => 'rewards.php', 'icon' => 'bi-star'],
        'manage' => ['label' => 'Manage', 'href' => 'index.php#manage', 'icon' => 'bi-list-check'],
        'dashboard' => ['label' => 'Dashboard', 'href' => '../../dashboard/dashboard.php', 'icon' => 'bi-grid'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= habit_e($title) ?> | Habit Garden</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Patrick+Hand&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="habit-garden.css">
</head>
<body>
<header class="site-header">
    <div class="site-header__inner">
        <a class="brand" href="index.php" aria-label="Habit Garden home">
            <span>Habit Garden</span>
            <img src="../../assets/habit/habit-leaf-sticker.png" alt="">
        </a>
        <nav class="site-nav" aria-label="Habit Garden navigation">
            <?php foreach ($pages as $key => $page): ?>
                <a class="<?= $activePage === $key ? 'is-active' : '' ?>" href="<?= habit_e($page['href']) ?>">
                    <i class="bi <?= habit_e($page['icon']) ?>" aria-hidden="true"></i>
                    <span><?= habit_e($page['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="profile-chip" title="Signed in as <?= habit_e(habit_current_user_name()) ?>">
            <img src="../../assets/habit/habit-heart-sticker.png" alt="">
            <span><?= habit_e(strtoupper(substr(habit_current_user_name(), 0, 1))) ?></span>
        </div>
    </div>
</header>
<main class="page-shell">
<?php
}

function habit_render_flash(?array $flash): void
{
    if (!$flash) {
        return;
    }

    $type = in_array($flash['type'] ?? '', ['success', 'error', 'info'], true)
        ? $flash['type']
        : 'info';
    $icon = match ($type) {
        'success' => 'bi-check-circle',
        'error' => 'bi-exclamation-circle',
        default => 'bi-info-circle',
    };
    ?>
    <div class="flash flash--<?= habit_e($type) ?>" role="status">
        <i class="bi <?= habit_e($icon) ?>" aria-hidden="true"></i>
        <span><?= habit_e($flash['message'] ?? '') ?></span>
    </div>
    <?php
}

function habit_render_footer(): void
{
    ?>
</main>
</body>
</html>
<?php
}

function habit_category_art(array $habit): string
{
    $source = strtolower(($habit['category_name'] ?? '') . ' ' . ($habit['category_icon'] ?? ''));

    if (str_contains($source, 'study') || str_contains($source, 'book') || str_contains($source, 'learn')) {
        return '../../assets/habit/habit-book-sticker.png';
    }
    if (str_contains($source, 'fitness') || str_contains($source, 'exercise') || str_contains($source, 'gym')) {
        return '../../assets/habit/habit-dumbbell-sticker.png';
    }
    if (str_contains($source, 'health') || str_contains($source, 'water')) {
        return '../../assets/habit/habit-heart-sticker.png';
    }

    return '../../assets/habit/habit-leaf-sticker.png';
}

function habit_reward_art(array $reward): string
{
    $code = strtolower((string) ($reward['reward_code'] ?? ''));
    if (str_contains($code, 'streak_3')) {
        return '../../assets/habit/streak-medal-sticker.png';
    }
    if (str_contains($code, 'streak_7')) {
        return '../../assets/habit/leaf-hat-sticker.png';
    }

    return '../../assets/habit/habit-garden-hero.png';
}

function habit_form_value(array $form, string $field, $fallback = ''): string
{
    return habit_e($form[$field] ?? $fallback);
}

function habit_render_field_error(array $errors, string $field): void
{
    if (!isset($errors[$field])) {
        return;
    }
    ?>
    <span class="field-error" id="<?= habit_e($field) ?>-error">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
        <?= habit_e($errors[$field]) ?>
    </span>
    <?php
}
