<?php

/**
 * Render the shared Light / Dark / System preference control.
 * Theme behavior lives in assets/js/theme.js so this component stays reusable.
 */
function renderGlobalThemeControl(): void
{
    ?>
    <div class="global-theme-control" data-theme-control role="group" aria-label="Theme preference">
        <span class="global-theme-label">Theme</span>
        <div class="global-theme-options">
            <button type="button" data-theme-option="light" aria-pressed="false" title="Use light theme">Light</button>
            <button type="button" data-theme-option="dark" aria-pressed="false" title="Use dark theme">Dark</button>
            <button type="button" data-theme-option="system" aria-pressed="false" title="Follow system theme">System</button>
        </div>
    </div>
    <?php
}
