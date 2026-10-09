/**
 * Client-side validation for every <form data-validate>.
 * (The server checks the same rules again — this is only for quick feedback.)
 *
 * Attributes understood on a field:
 *   required                 must not be blank
 *   data-label="Title"       friendly name used in messages
 *   data-min / data-max      trimmed length limits
 *   data-type="email"        email format
 *   data-strong              needs a letter and a number (passwords)
 *   data-match="#other"      must equal another field (confirm password)
 *   data-number              must be a number; with data-gt (greater than) and data-maxval
 *   data-int                 whole number between data-minval and data-maxval
 *
 * The error message goes into the <p class="field-error"> next to the field.
 * Listeners sit on the <form>, so rows added later by JavaScript are validated too.
 */
(function () {
    'use strict';

    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var CHECKED = 'input[required], textarea[required], select[required], [data-number], [data-int], [data-match]';

    function check(input) {
        var value = input.value;
        var trimmed = value.trim();
        var label = input.dataset.label || 'This field';
        var min = parseInt(input.dataset.min, 10);
        var max = parseInt(input.dataset.max, 10);

        if (input.hasAttribute('required') && trimmed === '') {
            return input.tagName === 'SELECT' ? 'Choose a ' + label.toLowerCase() + '.' : label + ' is required.';
        }
        if (trimmed === '') { return ''; }

        if (min && trimmed.length < min) {
            return label + ' must be at least ' + min + ' character' + (min === 1 ? '' : 's') + '.';
        }
        if (max && value.length > max) {
            return label + ' must be at most ' + max + ' characters.';
        }
        if (input.dataset.type === 'email' && !EMAIL_RE.test(trimmed)) {
            return 'Enter a valid email address.';
        }
        if (input.hasAttribute('data-strong') && !(/[A-Za-z]/.test(value) && /\d/.test(value))) {
            return 'Password must contain at least one letter and one number.';
        }
        if (input.dataset.match) {
            var other = document.querySelector(input.dataset.match);
            if (other && other.value !== value) { return 'Passwords do not match.'; }
        }
        if (input.hasAttribute('data-number')) {
            var n = Number(trimmed);
            var gt = input.dataset.gt !== undefined ? Number(input.dataset.gt) : null;
            var top = input.dataset.maxval !== undefined ? Number(input.dataset.maxval) : null;
            if (isNaN(n) || (gt !== null && n <= gt) || (top !== null && n > top)) {
                return 'Enter an amount above ' + (gt === null ? 0 : gt) + '.';
            }
        }
        if (input.hasAttribute('data-int')) {
            var i = Number(trimmed);
            var lo = Number(input.dataset.minval);
            var hi = Number(input.dataset.maxval);
            if (!Number.isInteger(i) || i < lo || i > hi) {
                return label + ' must be a whole number from ' + lo + ' to ' + hi + '.';
            }
        }
        return '';
    }

    function show(input, message) {
        var box = input.parentElement.querySelector('.field-error');
        if (box) { box.textContent = message; }
        input.classList.toggle('is-invalid', message !== '');
        input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
    }

    function validate(input) {
        var message = check(input);
        show(input, message);
        return message === '';
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        // validate a field when the user leaves it...
        form.addEventListener('focusout', function (event) {
            if (event.target.matches && event.target.matches(CHECKED)) { validate(event.target); }
        });
        // ...and keep re-checking while typing once it has shown an error
        form.addEventListener('input', function (event) {
            var t = event.target;
            if (t.matches && t.matches(CHECKED) && t.classList.contains('is-invalid')) { validate(t); }
        });

        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            form.querySelectorAll(CHECKED).forEach(function (input) {
                if (!validate(input) && !firstInvalid) { firstInvalid = input; }
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });

    // <form data-confirm="Are you sure?"> asks before deleting
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
        });
    });
})();
