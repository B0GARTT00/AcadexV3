// Course Outcomes Table JavaScript
// This file contains all JavaScript functionality for the course outcomes table

document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate CO Code and Identifier when modal is shown
    const modal = document.getElementById('addCourseOutcomeModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            generateNextCOCode();
        });
    }

    // Add double-click editing functionality for descriptions
    initializeEditableDescriptions();

    function generateNextCOCode() {
        // Get subject code from the courseOutcomesData global variable
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

        // Check if we've reached the 6 CO limit
        if (existingCOs.length >= 6) {
            alert('⚠️ Maximum Limit Reached\n\nThis subject already has 6 course outcomes, which is the maximum allowed.\n\nPlease delete an existing CO before adding a new one.');
            
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCourseOutcomeModal'));
            if (modal) {
                modal.hide();
            }
            return;
        }

        // Find the first missing CO number (1-6)
        let nextCONumber = null;
        for (let i = 1; i <= 6; i++) {
            if (!existingCOs.includes(i)) {
                nextCONumber = i;
                break;
            }
        }

        // If no missing number found (shouldn't happen due to limit check above)
        if (nextCONumber === null) {
            alert('⚠️ No Available CO Numbers\n\nAll CO positions (1-6) are occupied.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addCourseOutcomeModal'));
            if (modal) {
                modal.hide();
            }
            return;
        }

        // Set the auto-generated values
        const coCodeInput = document.getElementById('co_code');
        const coIdentifierInput = document.getElementById('co_identifier');
        
        if (coCodeInput && coIdentifierInput) {
            const newCOCode = `CO${nextCONumber}`;
            const newIdentifier = subjectCode ? `${subjectCode}.${nextCONumber}` : `CO${nextCONumber}`;
            
            coCodeInput.value = newCOCode;
            coIdentifierInput.value = newIdentifier;
            
            // Show helpful message about CO assignment
            const modalBody = document.querySelector('#addCourseOutcomeModal .modal-body');
            let infoAlert = modalBody.querySelector('.co-info-alert');
            
            if (!infoAlert) {
                infoAlert = document.createElement('div');
                infoAlert.className = 'alert alert-info border-0 mb-3 co-info-alert';
                infoAlert.style.background = 'rgba(13, 202, 240, 0.1)';
                modalBody.insertBefore(infoAlert, modalBody.firstChild);
            }
            
            const remainingSlots = 6 - existingCOs.length - 1; // -1 for the one being added
            infoAlert.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle text-info me-3 mt-1"></i>
                    <div>
                        <h6 class="text-info fw-bold mb-1">Course Outcome Assignment</h6>
                        <p class="mb-0 small">
                            <strong>Assigned:</strong> ${newCOCode} (${newIdentifier})<br>
                            <strong>Available slots:</strong> ${remainingSlots} of 6 remaining
                        </p>
                    </div>
                </div>
            `;
        }
    }

    function initializeEditableDescriptions() {
        // Set dynamic table height based on row count
        const tableContainer = document.querySelector('.course-outcomes-table-container');
        const scrollContainer = document.querySelector('.course-outcomes-table-scroll');
        const tableRows = document.querySelectorAll('.course-outcomes-table tbody tr');
        const rowCount = tableRows.length;
        
        if (tableContainer && scrollContainer) {
            // Calculate dynamic heights
            const rowHeight = 80; // Each row is 80px
            const headerHeight = 70; // Header and padding space
            const paddingBuffer = 50; // Extra buffer for spacing
            
            // Calculate minimum heights based on content
            const calculatedContainerHeight = (rowCount * rowHeight) + headerHeight + paddingBuffer;
            const calculatedScrollHeight = (rowCount * rowHeight) + headerHeight;
            
            // Apply heights with max limits
            const maxContainerHeight = 600;
            const maxScrollHeight = 550;
            
            const finalContainerHeight = Math.min(calculatedContainerHeight, maxContainerHeight);
            const finalScrollHeight = Math.min(calculatedScrollHeight, maxScrollHeight);
            
            // Set the calculated heights
            tableContainer.style.minHeight = finalContainerHeight + 'px';
            scrollContainer.style.minHeight = finalScrollHeight + 'px';
            
            // Enable scrolling if content exceeds max height
            if (calculatedScrollHeight > maxScrollHeight) {
                scrollContainer.style.overflowY = 'auto';
            }
        }

        // Add double-click event listeners to editable descriptions (only for authorized users)
        const userCanEdit = window.courseOutcomesData ? window.courseOutcomesData.userCanEdit : false;
        
        if (userCanEdit) {
            const editableDescriptions = document.querySelectorAll('.editable-description');
            editableDescriptions.forEach(function(element) {
                element.addEventListener('dblclick', function() {
                    makeEditable(element);
                });
            });
        }
    }
});

// Create description HTML with expand/collapse functionality
function createDescriptionHTML(text) {
    if (text.length <= 100) {
        return text;
    }
    
    const truncated = text.substring(0, 100);
    return `
        <span class="description-truncated">${truncated}...</span>
        <span class="description-full" style="display: none;">${text}</span>
        <button type="button" class="expand-toggle" onclick="toggleDescription(this)">Show more</button>
    `;
}

// Toggle description expand/collapse
function toggleDescription(button) {
    const container = button.closest('.description-container');
    const truncatedSpan = container.querySelector('.description-truncated');
    const fullSpan = container.querySelector('.description-full');
    const isExpanded = fullSpan.style.display !== 'none';
    
    if (isExpanded) {
        // Collapse
        truncatedSpan.style.display = 'inline';
        fullSpan.style.display = 'none';
        button.textContent = 'Show more';
    } else {
        // Expand
        truncatedSpan.style.display = 'none';
        fullSpan.style.display = 'inline';
        button.textContent = 'Show less';
    }
}

// Make description editable
function makeEditable(element) {
    // Check if user has permission to edit
    const userCanEdit = window.courseOutcomesData ? window.courseOutcomesData.userCanEdit : false;
    
    if (!userCanEdit) {
        // Show a toast message for unauthorized users
        showWarningToast('Access Denied', 'Only Chairperson and GE Coordinator can edit course outcome descriptions.');
        return;
    }
    
    const originalText = element.dataset.originalText;
    const coId = element.dataset.coId;
    const container = element.querySelector('.description-container');
    
    // Create textarea for editing
    const textarea = document.createElement('textarea');
    textarea.value = originalText;
    textarea.className = 'form-control form-control-sm';
    textarea.style.minHeight = '80px';
    textarea.style.resize = 'vertical';
    textarea.style.width = '100%';
    
    // Create save/cancel buttons
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'mt-2 d-flex gap-2';
    
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn btn-success btn-sm';
    saveBtn.innerHTML = '<i class="bi bi-check me-1"></i>Save';
    saveBtn.type = 'button';
    
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-secondary btn-sm';
    cancelBtn.innerHTML = '<i class="bi bi-x me-1"></i>Cancel';
    cancelBtn.type = 'button';
    
    buttonContainer.appendChild(saveBtn);
    buttonContainer.appendChild(cancelBtn);
    
    // Replace content with textarea and buttons
    container.innerHTML = '';
    container.appendChild(textarea);
    container.appendChild(buttonContainer);
    element.style.cursor = 'default';
    
    // Focus on textarea
    textarea.focus();
    textarea.select();
    
    // Save functionality
    saveBtn.addEventListener('click', function() {
        const newText = textarea.value.trim();
        
        // Remove any existing error styling
        textarea.classList.remove('textarea-error');
        
        // Check if the text is empty
        if (!newText) {
            // Add error styling to textarea
            textarea.classList.add('textarea-error');
            
            // Show user-friendly warning for empty submission
            showWarningToast('Description cannot be empty', 'Please enter a description before saving.');
            textarea.focus();
            
            // Remove error styling after a few seconds
            setTimeout(() => {
                textarea.classList.remove('textarea-error');
            }, 3000);
            return;
        }
        
        // Check if text hasn't changed
        if (newText === originalText) {
            restoreOriginal(element, originalText, container);
            showToast('No changes made', 'info');
            return;
        }
        
        // Proceed with update
        updateDescription(coId, newText, element, container);
    });
    
    // Cancel functionality
    cancelBtn.addEventListener('click', function() {
        restoreOriginal(element, originalText, container);
    });
    
    // Save on Enter, Cancel on Escape
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            saveBtn.click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelBtn.click();
        }
    });
}

function updateDescription(coId, newText, element, container) {
    // Show loading state
    container.innerHTML = '<div class="text-muted"><i class="bi bi-hourglass-split"></i> Updating...</div>';
    
    fetch(`/instructor/course_outcomes/${coId}/description`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            description: newText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the element with new text
            element.dataset.originalText = newText;
            element.title = newText;
            container.innerHTML = createDescriptionHTML(newText);
            element.style.cursor = 'pointer';
            
            // Show success feedback
            showToast('Description updated successfully', 'success');
        } else {
            throw new Error(data.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        restoreOriginal(element, element.dataset.originalText, container);
        showToast('Failed to update description', 'error');
    });
}

// Restore original description
function restoreOriginal(element, originalText, container) {
    // Reset the description text with expand/collapse functionality
    container.innerHTML = createDescriptionHTML(originalText);
    element.style.cursor = 'pointer';
}

// Show warning toast notification
function showWarningToast(title, message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1 text-warning-emphasis"></i>
                        <div>
                            <strong>${title}</strong><br>
                            <small>${message}</small>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 5000 // Show for 5 seconds
    });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'text-bg-success' : type === 'error' ? 'text-bg-danger' : 'text-bg-info';
    const iconClass = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${iconClass} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000
    });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
