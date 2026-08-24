(function () {
    'use strict';

    var confirmationMessage =
        'Are you sure you want to delete this user? This action cannot be undone and related user records may also be removed.';

    document.addEventListener('DOMContentLoaded', function () {
        var deleteForms = document.querySelectorAll('[data-admin-delete-form]');

        deleteForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!window.confirm(confirmationMessage)) {
                    return;
                }

                var submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Deleting...';
                }

                form.submit();
            });
        });
    });
}());
