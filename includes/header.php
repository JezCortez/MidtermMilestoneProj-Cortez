<?php
/** Expects: $pageTitle. Optional: $bodyClass. */
$pageTitle = $pageTitle ?? 'Everyday Kitchen';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (Auth::check()): ?>
        <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <?php endif; ?>
    <title><?= e($pageTitle) ?> · Everyday Kitchen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= Auth::check() ? 'index.php' : 'login.php' ?>">Everyday Kitchen</a>
        <nav class="nav" aria-label="Main">
            <?php if (Auth::check()): ?>
                <a href="index.php">Browse</a>
                <a href="index.php?mine=1">My recipes</a>
                <a href="favorites.php">Favorites</a>
                <a class="btn btn-primary btn-sm" href="recipe_form.php">Share a recipe</a>
                <form method="post" action="logout.php" class="inline-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm">Log out (<?= e(Auth::name()) ?>)</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php foreach (Flash::pull() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
<?php endforeach; ?>
