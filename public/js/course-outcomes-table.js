document.addEventListener('DOMContentLoaded', function () {
    // Sync toast progress bar with Bootstrap toast timer   
    function syncToastBar(toastId, barId, duration = 5000) {
        var toastEl = document.getElementById(toastId);
        var barEl = document.getElementById(barId);
        if (toastEl && barEl) {
            barEl.style.width = '100%';
            barEl.style.background = '#343a40'; // Bootstrap dark
            barEl.style.opacity = '1';
            var barDuration = 4000; // 4 seconds, faster than toast
            var start = Date.now();
            var interval = setInterval(function() {
                var elapsed = Date.now() - start;
                var percent = Math.max(0, 100 - (elapsed / barDuration) * 100);
                barEl.style.width = percent + '%';
                if (elapsed >= barDuration) {
                    barEl.style.width = '0%';
                    barEl.style.opacity = '0.5';
                    clearInterval(interval);
                }
            }, 16); // ~60fps
            // Listen for toast hidden event to clear bar immediately if closed early
            toastEl.addEventListener('hidden.bs.toast', function() {
                barEl.style.width = '0%';
                barEl.style.opacity = '0.5';
                clearInterval(interval);
            });
            // Use Bootstrap Toast API for auto-hide
            if (window.bootstrap && window.bootstrap.Toast) {
                var toastObj = bootstrap.Toast.getOrCreateInstance(toastEl);
                toastObj.show();
            }
        }
    }
    syncToastBar('toast-success', 'toast-success-bar');
    syncToastBar('toast-error', 'toast-error-bar');
    syncToastBar('toast-info', 'toast-info-bar');

    // Auto-generate CO Code and Identifier when add modal is shown
    const addModal = document.getElementById('addCourseOutcomeModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function(e) {
            generateNextCOCode();
        });
    }

    function generateNextCOCode() {
        // Get subject code from the page (this will be passed from Laravel)
        const subjectCode = window.courseOutcomesData ? window.courseOutcomesData.subjectCode : '';
        
        // Get existing course outcomes from the table
        const existingCOs = [];
        const coRows = document.querySelectorAll('tbody tr');
        
        coRows.forEach(row => {
            const coCodeCell = row.querySelector('td:first-child');
            if (coCodeCell) {
                const coCode = coCodeCell.textContent.trim();
                // Extract number from CO code (e.g., "CO1" -> 1)
                const match = coCode.match(/CO(\d+)/i);
                if (match) {
                    existingCOs.push(parseInt(match[1]));
                }
            }
        });

        // Determine next CO number
        let nextCONumber = 1;
        if (existingCOs.length > 0) {
            const maxCO = Math.max(...existingCOs);
            nextCONumber = maxCO + 1;
        }

        // Set the auto-generated values
        const coCodeInput = document.getElementById('co_code');
        const coIdentifierInput = document.getElementById('co_identifier');
        
        if (coCodeInput && coIdentifierInput) {
            const newCOCode = `CO${nextCONumber}`;
            const newIdentifier = subjectCode ? `${subjectCode}.${nextCONumber}` : `CO${nextCONumber}`;
            
            coCodeInput.value = newCOCode;
            coIdentifierInput.value = newIdentifier;
        }
    }

    // Handle expandable descriptions
    document.querySelectorAll('.expand-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const wrapper = this.closest('.description-wrapper');
            const textDiv = wrapper.querySelector('.description-text');
            
            if (textDiv.classList.contains('expanded')) {
                textDiv.classList.remove('expanded');
                this.innerHTML = '<small>Show more</small>';
            } else {
                textDiv.classList.add('expanded');
                this.innerHTML = '<small>Show less</small>';
            }
        });
    });

    // Handle inline editing of descriptions
    document.querySelectorAll('.editable-description').forEach(function(element) {
        element.addEventListener('dblclick', function() {
            const wrapper = this.closest('.description-wrapper');
            const input = wrapper.querySelector('.description-input');
            const textDiv = this;
            
            // Hide text and show input
            textDiv.classList.add('d-none');
            input.classList.remove('d-none');
            input.focus();
            input.select();
        });
    });

    // Handle input events for inline editing
    document.querySelectorAll('.description-input').forEach(function(input) {
        // Save on Enter key
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveDescription(this);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit(this);
            }
        });

        // Save on blur (clicking outside)
        input.addEventListener('blur', function() {
            saveDescription(this);
        });
    });

    function saveDescription(input) {
        const wrapper = input.closest('.description-wrapper');
        const textDiv = wrapper.querySelector('.description-text');
        const cell = input.closest('.co-description-cell');
        const coId = cell.getAttribute('data-co-id');
        const newDescription = input.value.trim();
        const originalText = textDiv.getAttribute('data-original-text');

        // If no change, just cancel
        if (newDescription === originalText) {
            cancelEdit(input);
            return;
        }

        // Validate input
        if (newDescription === '') {
            alert('Description cannot be empty');
            input.focus();
            return;
        }

        // Show loading state
        input.disabled = true;
        input.value = 'Saving...';

        // Get CSRF token
        const token = document.querySelector('meta[name="csrf-token"]');
        if (!token) {
            console.error('CSRF token not found');
            resetInputState(input, originalText);
            return;
        }

        // Send AJAX request
        fetch(`/instructor/course_outcomes/${coId}/description`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                description: newDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the display
                textDiv.textContent = data.description;
                textDiv.setAttribute('title', data.description);
                textDiv.setAttribute('data-original-text', data.description);
                
                // Hide input and show text
                input.classList.add('d-none');
                textDiv.classList.remove('d-none');
                
                // Reset input
                input.disabled = false;
                input.value = data.description;

                // Show success notification
                showNotification('Description updated successfully!', 'success');

                // Update expand button if needed
                updateExpandButton(wrapper, data.description);
            } else {
                throw new Error(data.message || 'Failed to update description');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMessage = 'Failed to update description. Please try again.';
            
            // Handle specific error cases
            if (error.message.includes('Failed to fetch')) {
                errorMessage = 'Network error. Please check your connection and try again.';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            showNotification(errorMessage, 'error');
            resetInputState(input, originalText);
        });
    }

    function cancelEdit(input) {
        const wrapper = input.closest('.description-wrapper');
        const textDiv = wrapper.querySelector('.description-text');
        const originalText = textDiv.getAttribute('data-original-text');
        
        // Reset input value
        input.value = originalText;
        input.disabled = false;
        
        // Hide input and show text
        input.classList.add('d-none');
        textDiv.classList.remove('d-none');
    }

    function resetInputState(input, originalText) {
        input.disabled = false;
        input.value = originalText;
        input.focus();
    }

    function updateExpandButton(wrapper, description) {
        const expandBtn = wrapper.querySelector('.expand-btn');
        
        if (description.length > 100) {
            if (!expandBtn) {
                // Create expand button if it doesn't exist
                const newBtn = document.createElement('button');
                newBtn.className = 'btn btn-link btn-sm p-0 mt-1 expand-btn';
                newBtn.type = 'button';
                newBtn.innerHTML = '<small>Show more</small>';
                wrapper.appendChild(newBtn);
                
                // Add event listener
                newBtn.addEventListener('click', function() {
                    const textDiv = wrapper.querySelector('.description-text');
                    
                    if (textDiv.classList.contains('expanded')) {
                        textDiv.classList.remove('expanded');
                        this.innerHTML = '<small>Show more</small>';
                    } else {
                        textDiv.classList.add('expanded');
                        this.innerHTML = '<small>Show less</small>';
                    }
                });
            }
        } else {
            // Remove expand button if text is short
            if (expandBtn) {
                expandBtn.remove();
            }
        }
    }

    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 350px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
});

// Initialize toast functionality when DOM is ready
function initializeToasts() {
    if (window.bootstrap && window.bootstrap.Toast) {
        // Initialize success toast
        const successToast = document.getElementById('toast-success');
        if (successToast) {
            const toastObj = bootstrap.Toast.getOrCreateInstance(successToast);
            toastObj.show();
        }
        
        // Initialize error toast
        const errorToast = document.getElementById('toast-error');
        if (errorToast) {
            const toastObj = bootstrap.Toast.getOrCreateInstance(errorToast);
            toastObj.show();
        }
        
        // Initialize info toast
        const infoToast = document.getElementById('toast-info');
        if (infoToast) {
            const toastObj = bootstrap.Toast.getOrCreateInstance(infoToast);
            toastObj.show();
        }
    }
}

// Call initialize function when DOM is ready
document.addEventListener('DOMContentLoaded', initializeToasts);
