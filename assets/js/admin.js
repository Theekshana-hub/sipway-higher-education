/**
 * SIPWAY Admin Scripts
 */
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle for mobile
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Image preview on file input change
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Select all checkboxes
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
});
