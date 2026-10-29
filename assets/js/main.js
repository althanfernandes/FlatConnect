/**
 * FlatConnect - Frontend JavaScript interactions
 * 
 * Provides responsive navigation, tab switching, client validation,
 * interactive property saving (favoriting), and enquiry modals.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Menu Toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            navMenu.classList.toggle('open');
        });
    }

    // 2. Auth Tab Switching (Login <-> Register)
    const tabButtons = document.querySelectorAll('.auth-tab-btn');
    const loginForm = document.getElementById('loginFormSection');
    const registerForm = document.getElementById('registerFormSection');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const target = this.getAttribute('data-target');
            if (target === 'register') {
                if (loginForm) loginForm.style.display = 'none';
                if (registerForm) registerForm.style.display = 'block';
            } else {
                if (loginForm) loginForm.style.display = 'block';
                if (registerForm) registerForm.style.display = 'none';
            }
        });
    });

    // Check URL hash for direct tab opening (e.g. #register)
    if (window.location.hash === '#register') {
        const registerTabBtn = document.querySelector('[data-target="register"]');
        if (registerTabBtn) registerTabBtn.click();
    }

    // 3. Client-side Form Validation for Registration
    const regForm = document.getElementById('userRegisterForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const pass = document.getElementById('reg_password');
            const confirm = document.getElementById('reg_confirm_password');
            const mobile = document.getElementById('reg_mobile');

            if (pass && confirm && pass.value !== confirm.value) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter both password fields.');
                confirm.focus();
                return;
            }

            if (mobile && mobile.value) {
                const cleaned = mobile.value.replace(/[^0-9]/g, '');
                if (cleaned.length < 10) {
                    e.preventDefault();
                    alert('Please enter a valid 10-digit mobile number.');
                    mobile.focus();
                    return;
                }
            }
        });
    }

    // 4. Save Property (Favorite) AJAX Toggle
    const saveButtons = document.querySelectorAll('.save-btn');
    saveButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const propertyId = this.getAttribute('data-id');
            if (!propertyId) return;

            fetch('save-property.php?id=' + encodeURIComponent(propertyId), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.saved) {
                        btn.classList.add('active');
                        btn.setAttribute('title', 'Remove from saved');
                    } else {
                        btn.classList.remove('active');
                        btn.setAttribute('title', 'Save property');
                    }
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Could not update saved property.');
                }
            })
            .catch(err => {
                console.error('Save error:', err);
            });
        });
    });

    // 5. Image Preview for Property Posting
    const photoInput = document.getElementById('property_photos');
    const previewContainer = document.getElementById('imagePreviewContainer');
    if (photoInput && previewContainer) {
        photoInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '90px';
                        img.style.height = '70px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '8px';
                        img.style.border = '1px solid #E2E8F0';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    }
});

// Modal helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
    }
}

// Close modals when clicking outside
window.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('open');
    }
});
