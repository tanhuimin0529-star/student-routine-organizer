(function () {
    'use strict';

    var STORAGE_KEY = 'studentRoutineOrganizerTheme';
    var ALLOWED_THEMES = ['light', 'dark', 'system'];
    var root = document.documentElement;
    var systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
    var currentPreference = readPreference();

    function isAllowedTheme(value) {
        return ALLOWED_THEMES.indexOf(value) !== -1;
    }

    function readPreference() {
        try {
            var savedTheme = window.localStorage.getItem(STORAGE_KEY);
            return isAllowedTheme(savedTheme) ? savedTheme : 'system';
        } catch (error) {
            return 'system';
        }
    }

    function savePreference(preference) {
        try {
            window.localStorage.setItem(STORAGE_KEY, preference);
        } catch (error) {
            // The selected theme still applies for this page if storage is unavailable.
        }
    }

    function resolveTheme(preference) {
        if (preference === 'system') {
            return systemTheme.matches ? 'dark' : 'light';
        }

        return preference;
    }

    function updateControls(resolvedTheme) {
        var toggles = document.querySelectorAll('[data-theme-toggle]');
        var isDark = resolvedTheme === 'dark';

        toggles.forEach(function (toggle) {
            var label = isDark ? 'Switch to light mode' : 'Switch to dark mode';
            var icon = toggle.querySelector('[data-theme-icon]');

            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');

            if (icon) {
                icon.textContent = isDark ? '☾' : '☀';
            }
        });

        // Retain support for any older three-option control markup.
        document.querySelectorAll('[data-theme-option]').forEach(function (option) {
            var isActive = option.getAttribute('data-theme-option') === currentPreference;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function applyTheme(preference, shouldSave) {
        if (!isAllowedTheme(preference)) {
            preference = 'system';
        }

        currentPreference = preference;

        var resolvedTheme = resolveTheme(preference);
        root.setAttribute('data-theme', resolvedTheme);
        root.setAttribute('data-theme-preference', preference);
        root.style.colorScheme = resolvedTheme;

        if (document.body) {
            document.body.classList.toggle('dark-mode', resolvedTheme === 'dark');
        }

        if (shouldSave) {
            savePreference(preference);
        }

        updateControls(resolvedTheme);
    }

    // Apply the stored preference immediately to reduce theme flashing while loading.
    applyTheme(currentPreference, false);

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(currentPreference, false);

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-theme-toggle]');

            if (toggle) {
                var currentTheme = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                applyTheme(currentTheme === 'dark' ? 'light' : 'dark', true);
                return;
            }

            var option = event.target.closest('[data-theme-option]');

            if (option) {
                applyTheme(option.getAttribute('data-theme-option'), true);
            }
        });
    });

    function handleSystemThemeChange() {
        if (currentPreference === 'system') {
            applyTheme('system', false);
        }
    }

    if (typeof systemTheme.addEventListener === 'function') {
        systemTheme.addEventListener('change', handleSystemThemeChange);
    } else if (typeof systemTheme.addListener === 'function') {
        systemTheme.addListener(handleSystemThemeChange);
    }

    window.addEventListener('storage', function (event) {
        if (event.key === STORAGE_KEY) {
            applyTheme(readPreference(), false);
        }
    });
})();