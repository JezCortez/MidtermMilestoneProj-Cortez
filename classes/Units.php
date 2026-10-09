<?php
declare(strict_types=1);

/** The measurement units a recipe may use (must match the ENUM in the ingredients table). */
final class Units
{
    /** group label => [stored key => label shown in the dropdown] */
    public const GROUPS = [
        'Metric' => ['g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'l'],
        'US'     => ['tsp' => 'tsp', 'tbsp' => 'tbsp', 'cup' => 'cup', 'fl_oz' => 'fl oz', 'oz' => 'oz', 'lb' => 'lb'],
        'Count'  => ['pc' => 'piece', 'clove' => 'clove', 'pinch' => 'pinch'],
    ];

    public static function isValid(string $key): bool
    {
        foreach (self::GROUPS as $units) {
            if (isset($units[$key])) {
                return true;
            }
        }
        return false;
    }

    /** Pretty label for the unit as printed next to a quantity. */
    public static function label(?string $key, float $qty = 1.0): string
    {
        return match ($key) {
            null    => '',
            'fl_oz' => 'fl oz',
            'cup'   => $qty > 1 ? 'cups' : 'cup',
            'pc'    => $qty > 1 ? 'pieces' : 'piece',
            'clove' => $qty > 1 ? 'cloves' : 'clove',
            'pinch' => $qty > 1 ? 'pinches' : 'pinch',
            default => $key,
        };
    }
}
