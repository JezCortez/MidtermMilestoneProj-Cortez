</main>
<footer class="site-footer">
    <div class="container">Everyday Kitchen · recipes shared by home cooks</div>
</footer>
<script src="assets/js/validation.js"></script>
<?php foreach (($extraScripts ?? []) as $script): ?>
<script src="assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
