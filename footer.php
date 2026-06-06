<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$useMainWrapper = !in_array($currentFile, ['transfer_true.php']);
if ($useMainWrapper):
?>
</main>
<?php endif; ?>
<footer class="site-footer">
    <div class="footer-inner">
        &copy; <?= date('Y') ?> Система управления заказами
    </div>
</footer>
</body>
</html>