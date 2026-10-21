

<?php
    $tema = auth()->check() ? (auth()->user()->tema ?? 'light') : 'light';
    if (!in_array($tema, ['light', 'dark'], true)) {
        $tema = 'light';
    }
?>

<script>
    window.__initTema = function () {
        const TEMA_KEY = 'tema';
        let tema = localStorage.getItem(TEMA_KEY) || '<?php echo e($tema); ?>';
        if (tema !== 'light' && tema !== 'dark') tema = 'light';

        const html = document.documentElement;
        html.dataset.tema = tema;
        html.classList.toggle('dark', tema === 'dark');
    };
    window.__initTema();
</script>

<?php echo $__env->make('partials.theme-css', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /var/www/resources/views/partials/theme.blade.php ENDPATH**/ ?>