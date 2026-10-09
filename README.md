# Everyday Kitchen — recipe sharing site

Plain PHP (OOP + PDO + sessions), HTML, CSS and vanilla JavaScript. No frameworks.

## Setup (XAMPP / Laragon, PHP 8.0+, MySQL/MariaDB)

1. Copy this folder into `htdocs`.

2. In phpMyAdmin → **Import** → `database.sql` (creates the `recipe_site` database, 6 tables, 8 starting categories).
3. *(Optional)* Import `sample_data.sql` for two demo accounts and four recipes (password for both: `Password1`).
4. Check the credentials in `db.php` (defaults: user `root`, empty password).
5. Open `http://localhost/<folder-name>/register.php`.

## Folder map

| Path | Purpose |

|---|---|
| `database.sql`, `sample_data.sql` | schema + categories, optional demo data |
| `db.php` | **the one database connection file** (`Database::connect()` returns the PDO) |
| `bootstrap.php` | required first by every page: loads db.php, helpers, autoloader, starts the session |
| `classes/` | `Auth`, `Csrf`, `Flash`, `Validator`, `Units`, `UserModel`, `CategoryModel`, `RecipeModel`, `RecipeForm`, `CommentModel`, `FavoriteModel` |
| `index.php` | browse: latest first, keyword search, category filter, "My recipes", pagination |
| `recipe.php` | one recipe: servings scaler, US/metric converter, steps, comments |
| `recipe_form.php` | share / edit a recipe (dynamic ingredient rows) |
| `recipe_delete.php`, `comment_edit.php`, `comment_delete.php` | owner-only changes |
| `favorites.php`, `api/favorite.php` | favorites list + the AJAX endpoint behind the ♥ button |
| `register.php`, `login.php`, `logout.php` | accounts (guest-only / logout) |
| `assets/js/` | `validation.js`, `ingredients.js`, `favorites.js`, `scaler.js` |
