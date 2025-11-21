</main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom JS -->
<script src="assets/js/main.js"></script>
<script src="../assets/js/custom.js"></script>

<script>
// Global JavaScript functions
function showAlert(message, type = 'success') {
    Swal.fire({
        icon: type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function confirmAction(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// Initialize DataTables
document.addEventListener('DOMContentLoaded', function() {
    const dataTables = document.querySelectorAll('.data-table');
    dataTables.forEach(table => {
        $(table).DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']]
        });
    });
});

// CSRF token for AJAX requests
const csrfToken = '<?php echo $_SESSION["csrf_token"] ?? ""; ?>';

// Add CSRF token to all AJAX requests
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        if (settings.type === 'POST') {
            settings.data += '&csrf_token=' + csrfToken;
        }
    }
});
</script>

</body>
</html>