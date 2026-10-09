/**
 * Servings scaler + US / metric converter for a recipe's ingredient list.
 *
 * Each <li> carries data-qty and data-unit (the amount as the author wrote it).
 * We recompute the displayed text from those every time, so repeated changes
 * never pile up rounding errors.
 */
(function () {
    'use strict';

    var box = document.getElementById('scaler');
    var list = document.getElementById('ingredient-list');
    if (!box || !list) { return; }

    var baseServings = parseInt(box.dataset.baseServings, 10) || 1;
    var servingsInput = document.getElementById('servings');
    var controls = box.querySelector('.scaler-controls');
    var segButtons = box.querySelectorAll('.seg');
    var system = 'original';

    controls.hidden = false;   // controls need JavaScript, so they start hidden

    /* ---- unit tables: everything converts through grams (weight) or millilitres (volume) ---- */
    var WEIGHT = { g: 1, kg: 1000, oz: 28.3495, lb: 453.592 };
    var VOLUME = { ml: 1, l: 1000, tsp: 4.92892, tbsp: 14.7868, cup: 236.588, fl_oz: 29.5735 };

    /* ---- number formatting ---- */
    var FRACTIONS = [[0, ''], [1 / 8, '\u215B'], [1 / 4, '\u00BC'], [1 / 3, '\u2153'], [3 / 8, '\u215C'],
                     [1 / 2, '\u00BD'], [5 / 8, '\u215D'], [2 / 3, '\u2154'], [3 / 4, '\u00BE'],
                     [7 / 8, '\u215E'], [1, '']];

    /** 2.5 -> "2½", 0.333 -> "⅓", 7 -> "7"; falls back to a decimal if no nice fraction fits. */
    function fraction(x) {
        var whole = Math.floor(x);
        var rest = x - whole;
        var best = null;
        FRACTIONS.forEach(function (f) {
            if (best === null || Math.abs(rest - f[0]) < Math.abs(rest - best[0])) { best = f; }
        });
        if (Math.abs(rest - best[0]) > 0.04) { return String(Math.round(x * 100) / 100); }
        if (best[0] === 1) { whole += 1; }
        var frac = best[0] === 1 ? '' : best[1];
        if (whole === 0 && frac) { return frac; }
        return whole + frac;
    }

    function plain(x) {                       // metric amounts: whole numbers, or up to 2 decimals when under 10
        return x < 10 ? String(Math.round(x * 100) / 100) : String(Math.round(x));
    }

    var LABELS = {
        g: 'g', kg: 'kg', ml: 'ml', l: 'l', tsp: 'tsp', tbsp: 'tbsp', oz: 'oz', lb: 'lb', fl_oz: 'fl oz',
        cup: ['cup', 'cups'], pc: ['piece', 'pieces'], clove: ['clove', 'cloves'], pinch: ['pinch', 'pinches']
    };

    function label(unit, amount) {
        var l = LABELS[unit];
        if (!l) { return ''; }
        return Array.isArray(l) ? (amount > 1 ? l[1] : l[0]) : l;
    }

    /* ---- conversion ---- */
    function convert(qty, unit) {
        // returns { amount, unit, text } for the current measurement system
        var inWeight = WEIGHT[unit] !== undefined;
        var inVolume = VOLUME[unit] !== undefined;

        if (system === 'metric') {
            if (inWeight) {
                var g = qty * WEIGHT[unit];
                return g >= 1000 ? metric(g / 1000, 'kg') : metric(g, 'g');
            }
            if (inVolume && unit !== 'tsp' && unit !== 'tbsp') {      // spoons are used in metric kitchens too
                var ml = qty * VOLUME[unit];
                return ml >= 1000 ? metric(ml / 1000, 'l') : metric(ml, 'ml');
            }
        }

        if (system === 'us') {
            if (inWeight) {
                var oz = qty * WEIGHT[unit] / WEIGHT.oz;
                return oz >= 16 ? us(oz / 16, 'lb') : us(oz, 'oz');
            }
            if (inVolume) {
                var mlTotal = qty * VOLUME[unit];
                if (unit === 'tsp' || unit === 'tbsp') { return us(qty, unit); }   // already US spoons
                if (mlTotal < 15) { return us(mlTotal / VOLUME.tsp, 'tsp'); }
                if (mlTotal < 59) { return us(mlTotal / VOLUME.tbsp, 'tbsp'); }
                return us(mlTotal / VOLUME.cup, 'cup');
            }
        }

        // "as written" (and counts): keep the unit, just tidy the number
        return (unit === 'g' || unit === 'kg' || unit === 'ml' || unit === 'l') ? metric(qty, unit) : us(qty, unit);
    }

    function metric(amount, unit) {
        return { text: plain(amount) + ' ' + unit };
    }
    function us(amount, unit) {
        var text = fraction(amount);
        return { text: (text + ' ' + label(unit, amount)).trim() };
    }

    /* ---- render ---- */
    function render() {
        var servings = parseFloat(servingsInput.value);
        if (!(servings >= 1)) { servings = baseServings; }
        var factor = servings / baseServings;

        list.querySelectorAll('li').forEach(function (li) {
            var raw = li.dataset.qty;
            var out = li.querySelector('.amount');
            if (raw === '' || raw === undefined) { out.textContent = label(li.dataset.unit, 1); return; }
            out.textContent = convert(parseFloat(raw) * factor, li.dataset.unit).text;
        });
    }

    /* ---- events ---- */
    function setServings(n) {
        n = Math.max(1, Math.min(100, n));
        servingsInput.value = n;
        render();
    }

    document.getElementById('serv-dec').addEventListener('click', function () {
        setServings((parseInt(servingsInput.value, 10) || baseServings) - 1);
    });
    document.getElementById('serv-inc').addEventListener('click', function () {
        setServings((parseInt(servingsInput.value, 10) || baseServings) + 1);
    });
    servingsInput.addEventListener('input', render);
    servingsInput.addEventListener('change', function () {
        setServings(parseInt(servingsInput.value, 10) || baseServings);
    });

    segButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            system = btn.dataset.system;
            segButtons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            render();
        });
    });

    render();
})();
