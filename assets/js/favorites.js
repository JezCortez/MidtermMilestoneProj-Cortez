/**
 * ♥ Save / Saved buttons — updates the favorites list without reloading the page (fetch + JSON).
 * On the Favorites page, un-saving also removes the card.
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.content : '';

    function paint(button, saved, count) {
        button.classList.toggle('is-saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        button.querySelector('.fav-icon').innerHTML = saved ? '&#9829;' : '&#9825;';
        button.querySelector('.fav-label').textContent = saved ? 'Saved' : 'Save';
        button.querySelector('.fav-count').textContent = count;
    }

    function removeCard(button) {
        var card = button.closest('.recipe-card');
        if (!card) { return; }
        card.classList.add('is-leaving');
        setTimeout(function () {
            card.remove();
            var grid = document.getElementById('favorites-grid');
            var empty = document.getElementById('favorites-empty');
            if (grid && empty && !grid.children.length) { empty.hidden = false; }
        }, 250);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.fav-btn');
        if (!button || button.disabled) { return; }

        var wasSaved = button.classList.contains('is-saved');
        button.disabled = true;   // avoid double clicks while the request runs

        fetch('api/favorite.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' },
            body: new URLSearchParams({
                recipe_id: button.dataset.recipeId,
                action: wasSaved ? 'remove' : 'add'
            })
        })
        .then(function (response) {
            if (response.status === 401) { window.location.href = 'login.php'; return null; }
            return response.json().then(function (data) { return { ok: response.ok, data: data }; });
        })
        .then(function (result) {
            if (!result) { return; }
            if (!result.ok || !result.data.ok) {
                window.alert(result.data.error || 'Something went wrong. Please try again.');
                return;
            }
            paint(button, result.data.favorited, result.data.count);
            if (!result.data.favorited && button.hasAttribute('data-remove-card')) { removeCard(button); }
        })
        .catch(function () {
            window.alert('Could not reach the server. Please try again.');
        })
        .then(function () { button.disabled = false; });
    });
})();
