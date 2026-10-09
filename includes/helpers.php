<?php
declare(strict_types=1);

/** Small functions used by every page. (Classes live in /classes.) */

/** Escape anything a user typed before printing it (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function format_date(?string $datetime): string
{
    return $datetime ? date('M j, Y', strtotime($datetime)) : '';
}

/** Positive integer from $_GET, or null. */
function get_id(string $key = 'id'): ?int
{
    $id = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return ($id === false || $id === null) ? null : $id;
}

/** Positive integer from $_POST, or null. */
function post_id(string $key = 'id'): ?int
{
    $id = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/** 2.500 -> "2.5", 3.000 -> "3" */
function fmt_qty(string|float|null $qty): string
{
    if ($qty === null) {
        return '';
    }
    return rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.');
}

/** The ♥ Save button. assets/js/favorites.js makes it work without a page reload. */
function fav_button(int $recipeId, bool $isFav, int $count, bool $removeCard = false): string
{
    return '<button type="button" class="fav-btn' . ($isFav ? ' is-saved' : '') . '"'
        . ' data-recipe-id="' . $recipeId . '"'
        . ' aria-pressed="' . ($isFav ? 'true' : 'false') . '"'
        . ($removeCard ? ' data-remove-card="1"' : '') . '>'
        . '<span class="fav-icon" aria-hidden="true">' . ($isFav ? '&#9829;' : '&#9825;') . '</span> '
        . '<span class="fav-label">' . ($isFav ? 'Saved' : 'Save') . '</span> '
        . '<span class="fav-count">' . $count . '</span></button>';
}
