<?php
/** Shared presentation helpers for authenticated student navigation. */

require_once __DIR__ . '/theme_control.php';

function sharedNavbarEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function renderSharedNavbarAssets(string $rootPrefix): void
{
    $prefix = rtrim($rootPrefix, '/') . '/';
    ?>
    <link rel="stylesheet" href="<?php echo sharedNavbarEscape($prefix . 'assets/css/global_navbar.css'); ?>">
    <?php
}

function renderSharedHeaderActions(string $rootPrefix, string $activeGlobal = ''): void
{
    $prefix = rtrim($rootPrefix, '/') . '/';
    ?>
    <nav class="module-shared-actions" aria-label="Account navigation">
        <a
            class="module-shared-actions__link<?php echo $activeGlobal === 'dashboard' ? ' is-active' : ''; ?>"
            href="<?php echo sharedNavbarEscape($prefix . 'dashboard/dashboard.php'); ?>"
            <?php echo $activeGlobal === 'dashboard' ? 'aria-current="page"' : ''; ?>
        >Dashboard</a>
        <a
            class="module-shared-actions__link<?php echo $activeGlobal === 'profile' ? ' is-active' : ''; ?>"
            href="<?php echo sharedNavbarEscape($prefix . 'dashboard/profile.php'); ?>"
            <?php echo $activeGlobal === 'profile' ? 'aria-current="page"' : ''; ?>
        >Profile</a>
        <span class="module-shared-actions__theme"><?php renderGlobalThemeControl(); ?></span>
        <a class="module-shared-actions__link module-shared-actions__logout" href="<?php echo sharedNavbarEscape($prefix . 'authentication/logout.php'); ?>">Logout</a>
    </nav>
    <?php
}

function renderSharedStudentNavbar(
    string $rootPrefix,
    string $activeModule = '',
    string $activeGlobal = ''
): void {
    $prefix = rtrim($rootPrefix, '/') . '/';
    ?>
    <header class="global-navbar">
        <div class="global-navbar__inner">
            <a class="global-navbar__brand" href="<?php echo sharedNavbarEscape($prefix . 'dashboard/dashboard.php'); ?>">
                Student Routine Organizer
            </a>
            <?php renderSharedHeaderActions($rootPrefix, $activeGlobal); ?>
        </div>
    </header>
    <?php
}

function sharedNavbarModuleConfiguration(): array
{
    return array(
        'diary' => array(
            'label' => 'Diary Journal',
            'home' => 'index.php',
            'links' => array(
                'home' => array('label' => 'Home', 'href' => 'index.php'),
                'favorites' => array('label' => 'Favorites', 'href' => 'index.php#favorite-entries'),
                'album' => array('label' => 'Memory Album', 'href' => 'memory_album.php'),
                'add' => array('label' => 'Add Entry', 'href' => 'add.php'),
            ),
        ),
        'money' => array(
            'label' => 'Money Tracker',
            'home' => 'index.php',
            'links' => array(
                'home' => array('label' => 'Home', 'href' => 'index.php'),
                'transactions' => array('label' => 'Transactions', 'href' => 'index.php'),
                'budget' => array('label' => 'Budget', 'href' => 'budget.php'),
            ),
        ),
        'exercise' => array(
            'label' => 'Exercise Tracker',
            'home' => 'dashboard.php',
            'links' => array(
                'home' => array('label' => 'Home', 'href' => 'dashboard.php'),
                'dashboard' => array('label' => 'Fitness Dashboard', 'href' => 'dashboard.php'),
                'records' => array('label' => 'My Records', 'href' => 'exercise_list.php'),
            ),
        ),
    );
}

function renderIntegratedModuleHeader(
    string $rootPrefix,
    string $module,
    string $activeItem = '',
    array $extraLinks = array()
): void {
    $modules = sharedNavbarModuleConfiguration();

    if (!isset($modules[$module])) {
        return;
    }

    $details = $modules[$module];
    $links = array_merge($details['links'], $extraLinks);
    ?>
    <header class="module-integrated-header module-integrated-header--<?php echo sharedNavbarEscape($module); ?>">
        <div class="module-integrated-header__inner">
            <a class="module-integrated-header__identity" href="<?php echo sharedNavbarEscape($details['home']); ?>">
                <?php echo sharedNavbarEscape($details['label']); ?>
            </a>
            <nav class="module-integrated-header__navigation" aria-label="<?php echo sharedNavbarEscape($details['label']); ?> navigation">
                <?php foreach ($links as $key => $link): ?>
                    <a
                        class="module-integrated-header__link<?php echo $activeItem === $key ? ' is-active' : ''; ?>"
                        href="<?php echo sharedNavbarEscape($link['href']); ?>"
                        <?php echo !empty($link['target']) ? 'target="' . sharedNavbarEscape($link['target']) . '" rel="noopener"' : ''; ?>
                        <?php echo $activeItem === $key ? 'aria-current="page"' : ''; ?>
                    ><?php echo sharedNavbarEscape($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
            <?php renderSharedHeaderActions($rootPrefix); ?>
        </div>
    </header>
    <?php
}