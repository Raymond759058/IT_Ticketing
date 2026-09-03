document.addEventListener('DOMContentLoaded', function () {

  // Sidebar toggle (mobile)
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
      });
    }
  }

  // Show/Hide Password toggle: <span class="password-toggle" data-target="#password">
  document.querySelectorAll('.password-toggle').forEach(function (el) {
    el.addEventListener('click', function () {
      const target = document.querySelector(el.dataset.target);
      if (!target) return;
      if (target.type === 'password') {
        target.type = 'text';
        el.classList.remove('bi-eye');
        el.classList.add('bi-eye-slash');
      } else {
        target.type = 'password';
        el.classList.remove('bi-eye-slash');
        el.classList.add('bi-eye');
      }
    });
  });

  // Generic delete confirmation
  document.querySelectorAll('.confirm-delete').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm || 'Are you sure you want to delete this item? This action cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert-auto-dismiss').forEach(function (el) {
    setTimeout(function () {
      const alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, 4000);
  });

});

// Utility: print current page (used on reports)
function printPage() {
  window.print();
}
