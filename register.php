<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

Auth::requireGuest();   // GUEST-only page

$users = new UserModel($pdo);
$v     = new Validator();
$old   = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid()) {
        $v->add('form', 'Your session expired. Please try again.');
    }

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');
    $old      = ['name' => $name, 'email' => $email];

    $v->text('name', $name, 'Name', 2, 50);
    $v->email('email', $email);
    $v->password('password', $password);
    if ($confirm === '') {
        $v->add('confirm_password', 'Please confirm your password.');
    } elseif ($password !== $confirm) {
        $v->add('confirm_password', 'Passwords do not match.');
    }

    if (!$v->has('email') && $users->emailExists($email)) {
        $v->add('email', 'That email is already registered.');
    }

    if ($v->passes()) {
        try {
            $users->create($name, $email, $password);
            Flash::add('success', 'Account created! You can now log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // UNIQUE(email) hit by a simultaneous sign-up
                $v->add('email', 'That email is already registered.');
            } else {
                error_log($e->getMessage());
                $v->add('form', 'Something went wrong. Please try again.');
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="card auth-card">
    <h1>Join Everyday Kitchen</h1>
    <?php if ($v->has('form')): ?><div class="alert alert-error"><?= e($v->first('form')) ?></div><?php endif; ?>

    <form method="post" action="register.php" novalidate data-validate>
        <?= Csrf::field() ?>

        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
                   required maxlength="50" data-label="Name" data-min="2" data-max="50" autocomplete="name">
            <p class="field-error"><?= e($v->first('name')) ?></p>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                   required maxlength="255" data-label="Email" data-type="email" autocomplete="email">
            <p class="field-error"><?= e($v->first('email')) ?></p>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required maxlength="72"
                   data-label="Password" data-min="8" data-max="72" data-strong autocomplete="new-password">
            <p class="hint">At least 8 characters, with a letter and a number.</p>
            <p class="field-error"><?= e($v->first('password')) ?></p>
        </div>

        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" required maxlength="72"
                   data-label="Confirm password" data-match="#password" autocomplete="new-password">
            <p class="field-error"><?= e($v->first('confirm_password')) ?></p>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="auth-switch">Already a member? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
