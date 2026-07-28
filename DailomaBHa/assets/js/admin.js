/**
 * Daailoma Bhariya - Admin Dashboard Interaction Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    initAlertAutoDismiss();
    initModalClosures();
});

/**
 * Automatically slides away diagnostic success alerts after 4 seconds
 * Leaves error banners un-mutated for troubleshooting visibility.
 */
function initAlertAutoDismiss() {
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
}

/**
 * Handles open/close events for administrative overlays
 */
function initModalClosures() {
    const modals = document.querySelectorAll('.admin-modal');
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('.btn-close-modal')) {
                modal.classList.remove('open');
            }
        });
    });
}

/**
 * Dynamically queries order line-items and drops them into a modal box
 * @param {number} orderId - The target order identifier
 */
async function launchOrderDetailsModal(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const container = document.getElementById('modalOrderItemsContainer');
    
    if (!modal || !container) return;
    
    // Set loading placeholder state
    container.innerHTML = '<p style="text-align:center; padding:2rem; color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Retrieving system transaction records...</p>';
    modal.classList.add('open');
    
    try {
        const response = await fetch(`../api/orders.php?action=get_details&order_id=${parseInt(orderId, 10)}`);
        const result = await response.json();
        
        if (result.success && Array.isArray(result.items)) {
            let htmlString = `
                <table style="width:100%; border-collapse:collapse; text-align:left;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--border-color); color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">
                            <th style="padding:0.75rem 0.5rem;">Product Item</th>
                            <th style="padding:0.75rem 0.5rem;">Qty</th>
                            <th style="padding:0.75rem 0.5rem;">Unit Cost</th>
                            <th style="padding:0.75rem 0.5rem; text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            result.items.forEach(item => {
                // Safeguard strings against raw DOM injection vulnerabilities
                const cleanName = typeof item.name === 'string' 
                    ? item.name.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;")
                    : `Product ID: ${item.product_id}`;
                
                const unitPrice = parseFloat(item.price) || 0;
                const quantity = parseInt(item.quantity, 10) || 0;
                const totalCost = quantity * unitPrice;

                htmlString += `
                    <tr style="border-bottom:1px solid var(--border-color); color:var(--text-dark);">
                        <td style="padding:1rem 0.5rem; font-weight:600;">${cleanName}</td>
                        <td style="padding:1rem 0.5rem; font-weight:500;">${quantity}</td>
                        <td style="padding:1rem 0.5rem; color:var(--text-muted);">Rs. ${unitPrice.toFixed(2)}</td>
                        <td style="padding:1rem 0.5rem; text-align:right; font-weight:700; color:var(--primary-dark, var(--text-dark));">Rs. ${totalCost.toFixed(2)}</td>
                    </tr>
                `;
            });
            
            htmlString += '</tbody></table>';
            container.innerHTML = htmlString;
        } else {
            container.innerHTML = `<p style="color:#ef4444; text-align:center; padding:1rem; font-weight:600;">${result.message || 'Failed to resolve ledger structure.'}</p>`;
        }
    } catch (error) {
        container.innerHTML = '<p style="color:#ef4444; text-align:center; padding:1rem; font-weight:600;"><i class="fa-solid fa-circle-exclamation"></i> Network execution timeout reading transaction details.</p>';
    }
}

/**
 * Safe confirmation interceptor for sensitive item removal operations
 * @param {Event} event - The triggered event context
 * @param {string} itemName - Product identity descriptor label
 */
function interceptDeletion(event, itemName) {
    if (!confirm(`Are you absolutely sure you want to remove "${itemName}" from the master active index?`)) {
        event.preventDefault();
        return false;
    }
    return true;
}