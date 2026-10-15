<?php
/**
 * Pagination component
 * Expects $pagination array from paginate() function
 * and optional $baseUrl
 */
if (!isset($pagination) || $pagination['total_pages'] <= 1) {
    return;
}
$baseUrl = $baseUrl ?? '';
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$queryPrefix = $queryString ? '?' . $queryString . '&' : '?';
?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $baseUrl . $queryPrefix . 'page=' . ($pagination['current_page'] - 1); ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo $baseUrl . $queryPrefix . 'page=1'; ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif;
        endif;
        for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo $baseUrl . $queryPrefix . 'page=' . $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor;
        if ($end < $pagination['total_pages']): ?>
            <?php if ($end < $pagination['total_pages'] - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?php echo $baseUrl . $queryPrefix . 'page=' . $pagination['total_pages']; ?>"><?php echo $pagination['total_pages']; ?></a></li>
        <?php endif; ?>
        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $baseUrl . $queryPrefix . 'page=' . ($pagination['current_page'] + 1); ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
