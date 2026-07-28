document.addEventListener("DOMContentLoaded", () => {
    /**
     * Elegant system toast notification generator
     * Maps securely with root theme color profiles
     */
    window.showToast = (message, type = "success") => {
        const toast = document.createElement("div");
        toast.className = `toast-notification ${type}`;
        
        // Element Structural Framework Settings
        toast.style.position = "fixed";
        toast.style.bottom = "24px";
        toast.style.right = "24px";
        toast.style.background = type === "success" ? "var(--primary, #10b981)" : "#ef4444";
        toast.style.color = "#ffffff";
        toast.style.padding = "14px 28px";
        toast.style.borderRadius = "var(--radius-md, 12px)";
        toast.style.boxShadow = "var(--shadow-md, 0 10px 15px -3px rgba(0,0,0,0.1))";
        toast.style.zIndex = "10000";
        toast.style.fontWeight = "600";
        toast.style.fontSize = "0.95rem";
        toast.style.transition = "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)";
        
        // Sanitize runtime output parameters safely
        toast.textContent = message;

        document.body.appendChild(toast);
        
        // Slide out animation lifecycle sequence step hooks
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";
            setTimeout(() => toast.remove(), 300);
        }, 3700);
    };
});

/**
 * Clean Frameworkless AJAX Engine for Inventory Queries
 * @param {Object} filters - Core tracking parameters to pipe downstream
 */
async function fetchProducts(filters = {}) {
    const params = new URLSearchParams(filters).toString();
    const container = document.getElementById("product-container");
    if (!container) return;

    try {
        // Safe relative address reference decoupling
        const response = await fetch(`api/products.php?${params}`);
        if (!response.ok) throw new Error(`HTTP network error state: ${response.status}`);
        
        const data = await response.json();
        
        if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-box-open" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-weight: 600;">No products found matching those catalog parameters.</p>
                </div>`;
            return;
        }

        container.innerHTML = data.map(product => {
            // Mitigation layers against arbitrary inline XSS tracking vectors
            const cleanName = (product.name || '').replace(/"/g, '&quot;');
            const cleanBrand = (product.brand || '').replace(/"/g, '&quot;');
            const displayPrice = parseFloat(product.price).toFixed(2);
            const imagePath = product.image ? product.image : 'assets/images/placeholder.jpg';

            return `
                <div class="card product-card">
                    <div class="product-image-wrapper" style="overflow:hidden; border-radius: var(--radius-md) var(--radius-md) 0 0;">
                        <img src="${imagePath}" alt="${cleanName}" style="width:100%; height:220px; object-fit:cover;">
                    </div>
                    <div class="product-details" style="padding: 1.25rem;">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem;">${cleanName}</h3>
                        <p class="brand" style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 1rem;">${cleanBrand}</p>
                        <div class="price-row" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="price" style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">Rs. ${displayPrice}</span>
                            <button onclick="addToCart(${parseInt(product.id, 10)})" class="btn-primary" style="padding: 0.6rem 1.1rem; font-size: 0.88rem; font-weight: 700; border-radius: var(--radius-md);">Add to Cart</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error("Pipeline failure fetching stock parameters:", err);
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: #ef4444;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                <p style="font-weight: 700;">Failed to load storefront catalog items. Please reload page.</p>
            </div>`;
    }
}