<?php
// pagination_lib.php
// Har listing page ke liye common pagination helper.
// Usage:
//   $pg = lib_paginate($total_records);
//   ... query mein LIMIT $pg['per_page'] OFFSET $pg['offset'] ...
//   echo lib_pagination_controls($pg);

function lib_paginate($total_records, $extra_params = []) {
    $allowed_sizes = [10, 50, 100, 250, 500, 1000];

    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    if (!in_array($per_page, $allowed_sizes, true)) {
        $per_page = 10;
    }

    $total_pages = max(1, (int)ceil($total_records / $per_page));
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;

    return [
        'per_page'     => $per_page,
        'page'         => $page,
        'total_pages'  => $total_pages,
        'offset'       => $offset,
        'total'        => $total_records,
        'allowed'      => $allowed_sizes,
        'extra_params' => $extra_params,
    ];
}

function lib_pagination_controls($pg) {
    $base_params = $pg['extra_params'];

    $build_url = function ($overrides) use ($base_params) {
        $params = array_merge($base_params, $_GET, $overrides);
        unset($params['_']);
        return '?' . http_build_query($params);
    };

    ob_start();
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center my-3 gap-2">
        <div class="text-muted small">
            <?php echo $pg['total']; ?> record(s) total — page <?php echo $pg['page']; ?> of <?php echo $pg['total_pages']; ?>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <?php foreach ($base_params as $k => $v) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php } ?>
            <label class="small text-muted mb-0">Show:</label>
            <select name="per_page" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ($pg['allowed'] as $size) { ?>
                    <option value="<?php echo $size; ?>" <?php echo $size == $pg['per_page'] ? 'selected' : ''; ?>><?php echo $size; ?></option>
                <?php } ?>
            </select>
            <input type="hidden" name="page" value="1">
        </form>
    </div>
    <?php if ($pg['total_pages'] > 1) { ?>
    <nav>
        <ul class="pagination pagination-sm flex-wrap">
            <?php
            $p = $pg['page'];
            $tp = $pg['total_pages'];

            // Prev
            $prev_disabled = $p <= 1 ? 'disabled' : '';
            echo '<li class="page-item ' . $prev_disabled . '"><a class="page-link" href="' . $build_url(['page' => max(1, $p - 1)]) . '">&laquo; Prev</a></li>';

            // Page number window (max 7 visible)
            $start = max(1, $p - 3);
            $end   = min($tp, $start + 6);
            $start = max(1, $end - 6);

            if ($start > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . $build_url(['page' => 1]) . '">1</a></li>';
                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            for ($i = $start; $i <= $end; $i++) {
                $active = $i == $p ? 'active' : '';
                echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $build_url(['page' => $i]) . '">' . $i . '</a></li>';
            }
            if ($end < $tp) {
                if ($end < $tp - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $build_url(['page' => $tp]) . '">' . $tp . '</a></li>';
            }

            // Next
            $next_disabled = $p >= $tp ? 'disabled' : '';
            echo '<li class="page-item ' . $next_disabled . '"><a class="page-link" href="' . $build_url(['page' => min($tp, $p + 1)]) . '">Next &raquo;</a></li>';
            ?>
        </ul>
    </nav>
    <?php } ?>
    <?php
    return ob_get_clean();
}