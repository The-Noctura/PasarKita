// Admin Dashboard JavaScript
// Minimal JS for now

// Modal functions (generic delete target)
let deleteTarget = { type: null, id: null };
let deleteTransactionId = null;

function showDeleteModal(type, id) {
    deleteTarget.type = type;
    deleteTarget.id = id;
    const modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'block';
}

function hideDeleteModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';
    deleteTarget.type = null;
    deleteTarget.id = null;
}

function confirmDelete() {
    if (!deleteTarget || !deleteTarget.type || !deleteTarget.id) return;
    if (deleteTarget.type === 'user') {
        window.location.href = 'delete_user.php?id=' + deleteTarget.id;
    } else if (deleteTarget.type === 'plan') {
        window.location.href = 'delete_plan.php?id=' + deleteTarget.id;
    }
}

function showDeleteTransactionModal(transactionId) {
    deleteTransactionId = transactionId;
    const modal = document.getElementById('confirmTransactionModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function hideDeleteTransactionModal() {
    const modal = document.getElementById('confirmTransactionModal');
    if (modal) {
        modal.style.display = 'none';
    }
    deleteTransactionId = null;
}

function confirmDeleteTransaction() {
    if (deleteTransactionId) {
        window.location.href = 'delete_transaction.php?id=' + deleteTransactionId;
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmTransactionBtn = document.getElementById('confirmTransactionBtn');
    const cancelTransactionBtn = document.getElementById('cancelTransactionBtn');
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmDelete);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideDeleteModal);
    }

    if (confirmTransactionBtn) {
        confirmTransactionBtn.addEventListener('click', confirmDeleteTransaction);
    }

    if (cancelTransactionBtn) {
        cancelTransactionBtn.addEventListener('click', hideDeleteTransactionModal);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmModal');
        if (event.target === modal) {
            hideDeleteModal();
        }

        const transactionModal = document.getElementById('confirmTransactionModal');
        if (event.target === transactionModal) {
            hideDeleteTransactionModal();
        }
    });
    
    // Close modal with Escape key
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideDeleteModal();
            hideDeleteTransactionModal();
        }
    });

    initAdminMobileNav();

    initDateRangePicker();
});

function initAdminMobileNav() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.nav-toggle');
    const nav = document.getElementById('adminNav');

    if (!sidebar || !toggleBtn || !nav) {
        return;
    }

    const mobileQuery = window.matchMedia('(max-width: 768px)');

    function setOpen(isOpen) {
        sidebar.classList.toggle('is-open', isOpen);
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    setOpen(false);

    toggleBtn.addEventListener('click', function() {
        const isOpen = sidebar.classList.contains('is-open');
        setOpen(!isOpen);
    });

    document.addEventListener('click', function(event) {
        if (!mobileQuery.matches) {
            return;
        }
        if (!sidebar.classList.contains('is-open')) {
            return;
        }
        if (sidebar.contains(event.target)) {
            return;
        }
        setOpen(false);
    });

    nav.addEventListener('click', function(event) {
        if (!mobileQuery.matches) {
            return;
        }
        const linkLike = event.target.closest('.nav-link');
        if (linkLike) {
            setOpen(false);
        }
    });

    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    window.addEventListener('resize', function() {
        if (!mobileQuery.matches) {
            setOpen(false);
        }
    });
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

function updatePerPage(limit) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', limit);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function initDateRangePicker() {
    const input = document.getElementById('date_range');
    if (!input || typeof flatpickr === 'undefined') {
        return;
    }

    const picker = flatpickr(input, {
        mode: 'range',
        dateFormat: 'd/m/Y',
        locale: {
            rangeSeparator: ' ~ ',
        },
    });

    const initialRange = input.getAttribute('data-initial-range');
    if (initialRange) {
        const parts = initialRange.split('~').map((part) => part.trim()).filter(Boolean);
        if (parts.length >= 1) {
            const start = parts[0];
            const end = parts.length > 1 ? parts[1] : start;
            picker.setDate([start, end], false, 'd/m/Y');
        }
    }
}