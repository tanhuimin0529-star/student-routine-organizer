<?php

/**
 * Render the shared global theme toggle.
 *
 * Use floating mode on pages without a suitable header action area.
 */
function renderGlobalThemeControl(bool $floating = false): void
{
    $class_name = 'global-theme-control';

    if ($floating) {
        $class_name .= ' global-theme-control--floating';
    }
    ?>
    <div class="<?php echo $class_name; ?>" data-theme-control>
        <button
            class="global-theme-toggle"
            type="button"
            data-theme-toggle
            aria-label="Switch to dark mode"
            title="Switch to dark mode"
        >
            <span data-theme-icon aria-hidden="true">☀</span>
        </button>
    </div>
    <?php
}