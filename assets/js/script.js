// SIKHA v3.0 - UI Enhancements

// ===== SIDEBAR TOGGLE (Mobile) =====
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('show');
}

// Close sidebar on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    }
});

// ===== SIDEBAR COLLAPSE (Desktop) =====
function toggleDesktopSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
    }
}

// ===== USER DROPDOWN MENU =====
function toggleUserMenu(event) {
    if (event) {
        event.stopPropagation();
    }
    var menu = document.getElementById('userDropdownMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Close user dropdown on outside click
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('userDropdown');
    var menu = document.getElementById('userDropdownMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.remove('show');
    }
});

// ===== THREE-DOT DROPDOWN MENU =====
function toggleDotMenu(btn, menuId) {
    // Close all other dot menus
    document.querySelectorAll('.dot-menu-items.show').forEach(function(m) {
        m.classList.remove('show');
        // Return to original parent
        if (m.dataset.originalParent) {
            document.getElementById(m.dataset.originalParent).appendChild(m);
            delete m.dataset.originalParent;
        }
    });
    var menu = document.getElementById(menuId);
    if (menu) {
        if (!menu.classList.contains('show')) {
            // Move to body to escape overflow containers
            var originalParent = menu.parentElement;
            if (!originalParent.id) originalParent.id = 'dot-menu-parent-' + menuId;
            menu.dataset.originalParent = originalParent.id;
            document.body.appendChild(menu);
            // Position relative to trigger button
            var rect = btn.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + 4) + 'px';
            menu.style.right = (window.innerWidth - rect.right) + 'px';
            menu.style.left = 'auto';
            menu.style.zIndex = '9999';
        }
        menu.classList.toggle('show');
    }
}

// Close dot menus on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dot-menu')) {
        document.querySelectorAll('.dot-menu-items.show').forEach(function(m) {
            m.classList.remove('show');
            m.style.position = '';
            m.style.top = '';
            m.style.left = '';
            m.style.right = '';
            m.style.zIndex = '';
            if (m.dataset.originalParent) {
                document.getElementById(m.dataset.originalParent).appendChild(m);
                delete m.dataset.originalParent;
            }
        });
    }
});

// ===== MODAL =====
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(function(m) {
            m.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
});

// ===== TOAST NOTIFICATION =====
function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:0.5rem;pointer-events:none;max-width:24rem;width:100%;';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.style.cssText = 'background:#fff;border-radius:0.75rem;border:1px solid #e2e8f0;padding:0.75rem 1rem;box-shadow:0 10px 30px rgba(0,0,0,0.08);animation:fadeSlideUp 0.3s ease-out;pointer-events:auto;display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;font-weight:500;';

    var icon = document.createElement('span');
    icon.style.cssText = 'flex-shrink:0;width:1.25rem;height:1.25rem;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:#fff;';

    if (type === 'success') {
        icon.style.background = '#22c55e';
        icon.textContent = '✓';
    } else if (type === 'error') {
        icon.style.background = '#ef4444';
        icon.textContent = '✗';
    } else {
        icon.style.background = '#2563eb';
        icon.textContent = 'i';
    }

    toast.appendChild(icon);
    toast.appendChild(document.createTextNode(message));
    container.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }, 3000);
}

// ===== LIVE CLOCK =====
function updateLiveClock() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var mins = String(now.getMinutes()).padStart(2, '0');
    var secs = String(now.getSeconds()).padStart(2, '0');
    var timeEl = document.getElementById('liveTime');
    var dateEl = document.getElementById('liveDate');
    if (timeEl) timeEl.textContent = hours + ':' + mins + ':' + secs;
    if (dateEl) {
        var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        dateEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
    }
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function() {
    // Close sidebar when clicking a nav link on mobile
    document.querySelectorAll('.sidebar-link').forEach(function(link) {
        link.addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            if (sidebar && window.innerWidth < 992) {
                sidebar.classList.remove('open');
                var overlay = document.getElementById('sidebarOverlay');
                if (overlay) overlay.classList.remove('show');
            }
        });
    });

    // Start live clock
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
});
