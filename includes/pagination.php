<?php
/**
 * Renders a Bootstrap 5 pagination bar.
 *
 * @param int      $page       Current page (1-based)
 * @param int      $totalPages Total number of pages
 * @param callable $urlFn      Receives (int $pageNum) and returns the URL string for that page
 */
function renderPagination(int $page, int $totalPages, callable $urlFn): void {
    if ($totalPages <= 1) return;

    $window = 2;
    $start  = max(1, $page - $window);
    $end    = min($totalPages, $page + $window);
    ?>
    <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-2 py-4">
        <nav aria-label="Page navigation">
            <ul class="pagination mb-0 flex-wrap justify-content-center">

                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($urlFn($page - 1)) ?>" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>

                <?php if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($urlFn(1)) ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($urlFn($i)) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($urlFn($totalPages)) ?>"><?= $totalPages ?></a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($urlFn($page + 1)) ?>" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>

            </ul>
        </nav>
        <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
    </div>
    <?php
}
?>
