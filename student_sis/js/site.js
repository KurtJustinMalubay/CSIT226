// ============================================
//  SIS - Student Information System JS
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // --- Mobile nav toggle ---
    const toggle = document.getElementById('navToggle');
    const mobile = document.getElementById('navMobile');
    if (toggle && mobile) {
        toggle.addEventListener('click', function () {
            mobile.classList.toggle('open');
            const spans = toggle.querySelectorAll('span');
            if (mobile.classList.contains('open')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            }
        });
    }

    // --- Live table search (dashboard) ---
    const searchInput = document.querySelector('.table-search input');
    if (searchInput) {
        // Debounce on typing for instant local filter feedback
        searchInput.addEventListener('input', debounce(function () {
            filterTable(this.value.toLowerCase());
        }, 200));
    }

    function filterTable(query) {
        const rows = document.querySelectorAll('tbody tr');
        let visible = 0;
        rows.forEach(function (row) {
            if (row.querySelector('.table-empty')) { return; }
            const text = row.innerText.toLowerCase();
            const match = text.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const counter = document.querySelector('.table-toolbar span');
        if (counter) {
            counter.textContent = visible + ' records found';
        }
    }

    // --- Form validation helpers ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            let valid = true;
            const u = document.getElementById('txtusername');
            const p = document.getElementById('txtpassword');
            if (!u.value.trim()) {
                showError('err_username', 'Username is required.'); highlight(u, 'error'); valid = false;
            } else { clearError('err_username'); highlight(u, 'ok'); }
            if (!p.value) {
                showError('err_password', 'Password is required.'); highlight(p, 'error'); valid = false;
            } else { clearError('err_password'); highlight(p, 'ok'); }
            if (!valid) e.preventDefault();
        });
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            let valid = true;
            const checks = [
                { id: 'txtfirstname', err: 'err_fname',  msg: 'First name is required.' },
                { id: 'txtlastname',  err: 'err_lname',  msg: 'Last name is required.' },
                { id: 'txtusername',  err: 'err_uname',  msg: 'Username is required.' },
            ];
            checks.forEach(function (c) {
                const el = document.getElementById(c.id);
                if (!el) return;
                if (!el.value.trim()) {
                    showError(c.err, c.msg); highlight(el, 'error'); valid = false;
                } else { clearError(c.err); highlight(el, 'ok'); }
            });
            const pwd  = document.getElementById('txtpassword');
            const cpwd = document.getElementById('txtconfirmpassword');
            if (pwd && pwd.value.length < 6) {
                showError('err_pwd', 'Password must be at least 6 characters.'); highlight(pwd, 'error'); valid = false;
            } else if (pwd) { clearError('err_pwd'); highlight(pwd, 'ok'); }
            if (cpwd && pwd && cpwd.value !== pwd.value) {
                showError('err_cpwd', 'Passwords do not match.'); highlight(cpwd, 'error'); valid = false;
            } else if (cpwd) { clearError('err_cpwd'); highlight(cpwd, 'ok'); }
            if (!valid) e.preventDefault();
        });
    }

    // Real-time password match indicator
    const cpwd = document.getElementById('txtconfirmpassword');
    const pwd  = document.getElementById('txtpassword');
    if (cpwd && pwd) {
        cpwd.addEventListener('input', function () {
            if (this.value === pwd.value) {
                clearError('err_cpwd'); highlight(this, 'ok');
            } else {
                showError('err_cpwd', 'Passwords do not match.'); highlight(this, 'error');
            }
        });
    }

    // --- Auto-dismiss alerts after 5s ---
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // --- Animate stat numbers on homepage ---
    document.querySelectorAll('.stat-number').forEach(function (el) {
        const target = parseInt(el.textContent.replace(/,/g, ''));
        if (isNaN(target) || target === 0) return;
        animateCount(el, 0, target, 1000);
    });

    // --- Helpers ---
    function showError(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.innerHTML = '<i class="fas fa-triangle-exclamation"></i> ' + msg; }
    }
    function clearError(id) {
        const el = document.getElementById(id);
        if (el) el.textContent = '';
    }
    function highlight(el, type) {
        el.style.borderColor = type === 'error' ? 'var(--danger)' : 'var(--success)';
        el.style.boxShadow   = type === 'error'
            ? '0 0 0 3px rgba(239,68,68,0.15)'
            : '0 0 0 3px rgba(34,197,94,0.12)';
    }
    function debounce(fn, delay) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn.bind(this, ...arguments), delay);
        };
    }
    function animateCount(el, start, end, duration) {
        const range = end - start;
        const step  = Math.ceil(range / (duration / 16));
        let current = start;
        const timer = setInterval(function () {
            current = Math.min(current + step, end);
            el.textContent = current.toLocaleString();
            if (current >= end) clearInterval(timer);
        }, 16);
    }
});
