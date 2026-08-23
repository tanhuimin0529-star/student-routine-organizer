(function () {
    'use strict';

    function initializeLoginSuccessMessages() {
        var messages = document.querySelectorAll('[data-auth-auto-dismiss="success"]');

        messages.forEach(function (message) {
            window.setTimeout(function () {
                message.classList.add('auth-alert-is-dismissing');

                window.setTimeout(function () {
                    message.remove();
                }, 260);
            }, 3000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLoginSuccessMessages);
    } else {
        initializeLoginSuccessMessages();
    }
}());
