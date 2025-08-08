<?php if (isLoggedIn() && !$hasActiveDisp): ?>
        </main>
    </div>
<?php endif; ?>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'unset';
            
            // Reset forms when closing modals
            const modal = document.getElementById(modalId);
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => {
                if (form.id !== 'editDisplacementForm') { // Don't reset edit forms automatically
                    form.reset();
                }
            });
            
            // Hide image previews
            const imagePreviews = modal.querySelectorAll('[id$="ImagePreview"], [id$="Preview"]');
            imagePreviews.forEach(preview => {
                preview.classList.add('hidden');
            });
            
            // Reset hidden inputs
            const hiddenInputs = modal.querySelectorAll('input[type="hidden"][name$="_id"], input[type="hidden"][name="photo"], input[type="hidden"][name="foto_cnh"]');
            hiddenInputs.forEach(input => {
                if (!input.name.includes('vehicle_id') && !input.name.includes('displacement_id')) {
                    input.value = '';
                }
            });
        }

        // Profile dropdown
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            const desktopDropdown = document.getElementById('desktopProfileDropdown');
            
            if (dropdown && window.innerWidth < 1024) {
                dropdown.classList.toggle('hidden');
            } else if (desktopDropdown && window.innerWidth >= 1024) {
                desktopDropdown.classList.toggle('hidden');
            }
        }
        
        // Mobile menu dropdown
        function toggleMobileMenu() {
            const dropdown = document.getElementById('mobileMenuDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const mobileDropdown = document.getElementById('profileDropdown');
            const desktopDropdown = document.getElementById('desktopProfileDropdown');
            const mobileMenu = document.getElementById('mobileMenuDropdown');
            const button = event.target.closest('button');
            
            if (!button || (!button.onclick || (button.onclick.toString().indexOf('toggleProfileDropdown') === -1 && button.onclick.toString().indexOf('toggleMobileMenu') === -1))) {
                if (mobileDropdown && !mobileDropdown.classList.contains('hidden')) {
                    mobileDropdown.classList.add('hidden');
                }
                if (desktopDropdown && !desktopDropdown.classList.contains('hidden')) {
                    desktopDropdown.classList.add('hidden');
                }
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });
        
        // Close modals when clicking on backdrop
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-backdrop')) {
                const modal = event.target.closest('[id$="Modal"]');
                if (modal) {
                    closeModal(modal.id);
                }
            }
        });
        
        // Close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const openModals = document.querySelectorAll('[id$="Modal"]:not(.hidden)');
                openModals.forEach(modal => {
                    closeModal(modal.id);
                });
                
                // Close mobile menu
                const mobileMenu = document.getElementById('mobileMenuDropdown');
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });

        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        // Show loading state
        function showLoading(buttonId) {
            const button = document.getElementById(buttonId);
            const originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Carregando...';
            button.disabled = true;
            lucide.createIcons();
            
            return function() {
                button.innerHTML = originalText;
                button.disabled = false;
                lucide.createIcons();
            };
        }

        // Success/Error messages
        function showMessage(message, type = 'success') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        // Auto-refresh for active displacement check
        <?php if (isLoggedIn()): ?>
        setInterval(() => {
            fetch('/frota/api/check-active-displacement.php')
                .then(response => response.json())
                .then(data => {
                    if (data.hasActiveDisplacement && window.location.pathname !== '/frota/active-displacement.php') {
                        window.location.href = '/frota/active-displacement.php';
                    }
                })
                .catch(error => console.error('Error checking active displacement:', error));
        }, 30000); // Check every 30 seconds
        <?php endif; ?>
    </script>
</body>
</html>