<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$sql = "SELECT bc.copy_id, bc.status, b.title, b.author
        FROM book_copies bc
        JOIN books b ON bc.book_id = b.book_id
        ORDER BY b.title ASC, bc.copy_id ASC";
$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . htmlspecialchars($conn->error));
}
$total = $result->num_rows;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Codes - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container mt-5 text-center">
    <h2 class="mb-4">📷 Book Copy QR Codes <small class="text-muted fs-6">(<span id="shownCount"><?php echo $total; ?></span> of <?php echo $total; ?>)</small></h2>

    <div class="no-print mb-3">
        <a href="index.php" class="btn btn-secondary">← Back to Home</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Shown QR Codes</button>
    </div>

    <div class="no-print row g-2 justify-content-center mb-4">
        <div class="col-md-5">
            <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search by title, author or copy ID">
        </div>
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="issued">Issued</option>
            </select>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-center gap-3" id="qrGrid">
        <?php if ($total == 0) { ?>
            <p class="text-muted">Abhi koi book copy nahi hai. Pehle "Add Book" se book add karo.</p>
        <?php } ?>
        <?php while ($row = $result->fetch_assoc()) {
            $copy_id = $row['copy_id'];
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($copy_id);
            $badge_class = $row['status'] == 'available' ? 'bg-success' : ($row['status'] == 'issued' ? 'bg-warning text-dark' : 'bg-secondary');
        ?>
        <div class="card p-3 qr-card" style="width: 200px;"
             data-search="<?php echo htmlspecialchars(strtolower($row['title'] . ' ' . $row['author'] . ' ' . $copy_id)); ?>"
             data-status="<?php echo htmlspecialchars($row['status']); ?>">
            <img src="<?php echo $qr_url; ?>" class="mx-auto" width="150" height="150" alt="QR for copy <?php echo htmlspecialchars($copy_id); ?>">
            <p class="mt-2 mb-0"><b><?php echo htmlspecialchars($row['title']); ?></b></p>
            <small class="text-muted"><?php echo htmlspecialchars($row['author']); ?></small>
            <p class="mb-1 mt-1"><strong>Copy ID: <?php echo htmlspecialchars($copy_id); ?></strong></p>
            <span class="badge <?php echo $badge_class; ?> no-print"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
        </div>
        <?php } ?>
    </div>
</div>

<script>
const searchBox = document.getElementById('searchBox');
const statusFilter = document.getElementById('statusFilter');
const cards = document.querySelectorAll('.qr-card');
const shownCount = document.getElementById('shownCount');

function applyFilters() {
    const q = searchBox.value.toLowerCase().trim();
    const s = statusFilter.value;
    let shown = 0;
    cards.forEach(card => {
        const ok = card.dataset.search.includes(q) && (s === '' || card.dataset.status === s);
        card.style.display = ok ? '' : 'none';
        if (ok) shown++;
    });
    shownCount.textContent = shown;
}
searchBox.addEventListener('input', applyFilters);
statusFilter.addEventListener('change', applyFilters);
</script>
</body>
</html>
<?php $conn->close(); ?>