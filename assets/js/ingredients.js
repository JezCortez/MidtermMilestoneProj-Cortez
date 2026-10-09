/**
 * "Add ingredient" button on the recipe form.
 * Clones the <template>, gives the new fields a fresh index (ingredients[3][name]...)
 * and lets members remove rows they don't need.
 * Also provides autocomplete suggestions for ingredient names.
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

    // Autocomplete state
    var activeInput = null;
    var activeSuggestions = null;
    var selectedIndex = -1;
    var fetchTimeout = null;

    function refreshButtons() {
        var count = list.querySelectorAll('.ing-row').length;
        addButton.disabled = count >= max;
        list.querySelectorAll('.remove-ing').forEach(function (btn) {
            btn.disabled = count === 1;   // always keep at least one row
        });
    }

    function createSuggestionsBox() {
        var box = document.createElement('div');
        box.className = 'ingredient-suggestions';
        box.style.display = 'none';
        document.body.appendChild(box);
        return box;
    }

    function positionSuggestionsBox(input, box) {
        var rect = input.getBoundingClientRect();
        box.style.position = 'absolute';
        box.style.top = (rect.bottom + window.scrollY) + 'px';
        box.style.left = (rect.left + window.scrollX) + 'px';
        box.style.width = rect.width + 'px';
    }

    function hideSuggestions() {
        if (activeSuggestions) {
            activeSuggestions.style.display = 'none';
            activeSuggestions.innerHTML = '';
        }
        selectedIndex = -1;
        activeInput = null;
    }

    function showSuggestions(input, suggestions) {
        if (!activeSuggestions) {
            activeSuggestions = createSuggestionsBox();
        }

        if (suggestions.length === 0) {
            hideSuggestions();
            return;
        }

        activeSuggestions.innerHTML = '';
        suggestions.forEach(function (name, index) {
            var item = document.createElement('div');
            item.className = 'ingredient-suggestion-item';
            item.textContent = name;
            item.dataset.index = String(index);
            activeSuggestions.appendChild(item);
        });

        positionSuggestionsBox(input, activeSuggestions);
        activeSuggestions.style.display = 'block';
        activeInput = input;
        selectedIndex = -1;
    }

    function fetchSuggestions(query, input) {
        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        clearTimeout(fetchTimeout);
        fetchTimeout = setTimeout(function () {
            fetch('api/ingredients.php?q=' + encodeURIComponent(query))
                .then(function (response) {
                    if (!response.ok) { throw new Error('Network error'); }
                    return response.json();
                })
                .then(function (data) {
                    if (Array.isArray(data) && data.length > 0) {
                        showSuggestions(input, data);
                    } else {
                        hideSuggestions();
                    }
                })
                .catch(function () {
                    hideSuggestions();
                });
        }, 200);
    }

    function selectSuggestion(index) {
        if (!activeSuggestions || !activeInput) { return; }
        var items = activeSuggestions.querySelectorAll('.ingredient-suggestion-item');
        if (index < 0 || index >= items.length) { return; }

        activeInput.value = items[index].textContent;
        hideSuggestions();
        activeInput.focus();
    }

    function highlightSuggestion(index) {
        if (!activeSuggestions) { return; }
        var items = activeSuggestions.querySelectorAll('.ingredient-suggestion-item');
        items.forEach(function (item, i) {
            item.classList.toggle('selected', i === index);
        });
    }

    function attachAutocomplete(input) {
        input.addEventListener('input', function () {
            fetchSuggestions(input.value.trim(), input);
        });

        input.addEventListener('keydown', function (event) {
            if (!activeSuggestions || activeSuggestions.style.display === 'none') { return; }

            var items = activeSuggestions.querySelectorAll('.ingredient-suggestion-item');
            if (items.length === 0) { return; }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                highlightSuggestion(selectedIndex);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
                highlightSuggestion(selectedIndex);
            } else if (event.key === 'Enter' && selectedIndex >= 0) {
                event.preventDefault();
                selectSuggestion(selectedIndex);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                hideSuggestions();
            }
        });

        input.addEventListener('blur', function () {
            // Delay to allow click on suggestion
            setTimeout(hideSuggestions, 200);
        });
    }

    // Click handler for suggestions
    document.addEventListener('click', function (event) {
        if (!activeSuggestions) { return; }
        var item = event.target.closest('.ingredient-suggestion-item');
        if (item && activeSuggestions.contains(item)) {
            event.preventDefault();
            var index = parseInt(item.dataset.index, 10);
            selectSuggestion(index);
        }
    });

    // Attach autocomplete to existing ingredient name inputs
    list.querySelectorAll('.cell-name input').forEach(attachAutocomplete);

    addButton.addEventListener('click', function () {
        if (list.querySelectorAll('.ing-row').length >= max) { return; }

        var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var row = holder.firstElementChild;
        list.appendChild(row);

        // Attach autocomplete to the new ingredient name input
        var nameInput = row.querySelector('.cell-name input');
        if (nameInput) {
            attachAutocomplete(nameInput);
        }

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
