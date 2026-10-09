<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireGuest();   // GUEST-only page

$users = new UserModel($pdo);
$v     = new Validator();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid()) {
        $v->add('form', 'Your session expired. Please try again.');
    }

    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '') {
        $v->add('email', 'Email is required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $v->add('email', 'Enter a valid email address.');
    }
    if ($password === '') {
        $v->add('password', 'Password is required.');
    }

    if ($v->passes()) {
        $user = $users->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            Auth::login((int) $user['id'], $user['name']);
            redirect('index.php');
        }
        // same message whether the email or the password was wrong
        $v->add('form', 'Invalid email or password.');
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <h1>Welcome back</h1>
    <?php if ($v->has('form')): ?><div class="alert alert-error"><?= e($v->first('form')) ?></div><?php endif; ?>

    <form method="post" action="login.php" novalidate data-validate>
        <?= Csrf::field() ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>"
                   required maxlength="255" data-label="Email" data-type="email" autocomplete="email">
            <p class="field-error"><?= e($v->first('email')) ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   data-label="Password" autocomplete="current-password">
            <p class="field-error"><?= e($v->first('password')) ?></p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>

    <p class="auth-switch">New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
