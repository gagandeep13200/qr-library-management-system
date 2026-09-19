<?php
// Iska use karne wale page mein URL me ?msg=... ya ?error=... hona chahiye
$toast_msg = $_GET['msg'] ?? null;
$toast_error = $_GET['error'] ?? null;
?>
<?php if ($toast_msg || $toast_error) { ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div class="toast align-items-center text-white <?php echo $toast_error ? 'bg-danger' : 'bg-success'; ?> border-0 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo htmlspecialchars($toast_error ?? $toast_msg); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(() => {
        document.querySelectorAll('.toast').forEach(t => {
            const toast = bootstrap.Toast.getOrCreateInstance(t);
            toast.hide();
        });
    }, 3500);
</script>
<?php } ?>