/**
 * "Add ingredient" button on the recipe form.
 * Clones the <template>, gives the new fields a fresh index (ingredients[3][name]...)
 * and lets members remove rows they don't need.
 */
(function () {
    'use strict';

    var list = document.getElementById('ingredient-rows');
    var template = document.getElementById('ingredient-template');
    var addButton = document.getElementById('add-ingredient');
    if (!list || !template || !addButton) { return; }

    var max = parseInt(list.dataset.max, 10) || 30;
    var nextIndex = list.querySelectorAll('.ing-row').length;
    var rowsError = document.getElementById('ingredients-error');

    function refreshButtons() {
        var count = list.querySelectorAll('.ing-row').length;
        addButton.disabled = count >= max;
        list.querySelectorAll('.remove-ing').forEach(function (btn) {
            btn.disabled = count === 1;   // always keep at least one row
        });
    }

    addButton.addEventListener('click', function () {
        if (list.querySelectorAll('.ing-row').length >= max) { return; }

        var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var row = holder.firstElementChild;
        list.appendChild(row);

        if (rowsError) { rowsError.textContent = ''; }
        refreshButtons();
        row.querySelector('input').focus();
    });

    list.addEventListener('click', function (event) {
        var btn = event.target.closest('.remove-ing');
        if (!btn) { return; }
        btn.closest('.ing-row').remove();
        refreshButtons();
    });

    refreshButtons();
})();
