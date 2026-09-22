    </main>
  </div>

  <!-- Global Admin Scripts -->
  <script>
    // Simple table search filter
    const searchInput = document.getElementById('adminGlobalSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.admin-table tbody tr');
        rows.forEach(row => {
          const text = row.innerText.toLowerCase();
          row.style.display = text.includes(query) ? '' : 'none';
        });
      });
    }

    // Mobile Sidebar Toggle & Close Handling
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.getElementById('adminSidebarOverlay');
    const sidebar = document.getElementById('adminSidebar');

    function openSidebar() {
      if (sidebar) sidebar.classList.add('show');
      if (overlay) overlay.classList.add('show');
      document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('show');
      if (overlay) overlay.classList.remove('show');
      document.body.classList.remove('sidebar-open');
    }

    if (toggleBtn) {
      toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (sidebar && sidebar.classList.contains('show')) {
          closeSidebar();
        } else {
          openSidebar();
        }
      });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Auto-close sidebar on mobile when a nav link is clicked
    document.querySelectorAll('.admin-sidebar .admin-nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
          closeSidebar();
        }
      });
    });

    // Close on Escape key press
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
        closeSidebar();
      }
    });
  </script>
</body>
</html>
