document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.admin-sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
    }
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        if (backdrop) backdrop.classList.toggle('show', sidebar.classList.contains('open'));
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', toggleSidebar);
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }
    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) closeSidebar();
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
