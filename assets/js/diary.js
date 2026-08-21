(function () {
    'use strict';

    var readingPage = document.querySelector('.diary-reading-page');
    var entryLinks = document.querySelectorAll('a.diary-entry-sequence-link');
    var animationDuration = 440;
    var navigationPending = false;

    if (!readingPage || entryLinks.length === 0) {
        return;
    }

    function prefersReducedMotion() {
        return window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function resetPageTurn() {
        readingPage.classList.remove('is-turning-next', 'is-turning-previous');
        navigationPending = false;
    }

    entryLinks.forEach(function (entryLink) {
        entryLink.addEventListener('click', function (event) {
            var isModifiedClick = event.button !== 0
                || event.metaKey
                || event.ctrlKey
                || event.shiftKey
                || event.altKey;

            if (isModifiedClick || prefersReducedMotion()) {
                return;
            }

            if (navigationPending) {
                event.preventDefault();
                return;
            }

            var targetUrl = entryLink.href;

            if (!targetUrl) {
                return;
            }

            event.preventDefault();
            navigationPending = true;

            var animationClass = entryLink.classList.contains('diary-entry-sequence-next')
                ? 'is-turning-next'
                : 'is-turning-previous';

            readingPage.classList.add(animationClass);

            window.setTimeout(function () {
                window.location.href = targetUrl;
            }, animationDuration);
        });
    });

    window.addEventListener('pageshow', resetPageTurn);
}());
