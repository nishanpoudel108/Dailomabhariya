<?php
/**
 * Global Helper Functions & Sanity Engines
 * Daailoma Bhariya Production Sandbox
 */

/**
 * Escapes variable outputs safely for template rendering contexts.
 * Handles potential null parameters smoothly without throwing scalar type errors.
 */
function sanitize_output(?string $data): string {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats currency calculations directly to standard Nepalese format metrics.
 */
function format_nepali_currency(float $amount): string {
    return "Rs. " . number_format($amount, 2);
}

/**
 * Quantifies mathematical discount reductions safely against baseline inventory values.
 */
function calculate_discount_price(float $price, float $discountPercentage): float {
    if ($discountPercentage <= 0.0) return $price;
    if ($discountPercentage >= 100.0) return 0.0;
    return $price - ($price * ($discountPercentage / 100.0));
}

/**
 * Returns theme-compatible tracking system color tokens.
 * Maps variable tokens seamlessly with dark and light stylesheets.
 */
function get_tracking_status_color(string $status): string {
    switch (trim($status)) {
        case 'Pending': 
            return 'var(--text-muted, #94a3b8)';
        case 'Accepted': 
            return '#3b82f6'; // System Blue
        case 'Packing': 
            return '#f59e0b'; // System Amber
        case 'Ready': 
            return '#8b5cf6'; // System Purple
        case 'Out for Delivery': 
            return '#06b6d4'; // System Cyan
        case 'Delivered': 
            return 'var(--primary, #10b981)';
        case 'Cancelled': 
            return '#ef4444'; // System Red
        default: 
            return 'var(--text-muted, #64748b)';
    }
}

/**
 * Logs atomic platform actions directly into the database system ledger.
 * Inspects proxy layers to extract exact IP strings safely.
 */
function log_system_event(?int $userId, string $action): void {
    try {
        $db = Database::getInstance();
        
        // Resolve target routing metrics through load balancers or proxy switches
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($forwardedIps[0]);
        }
        
        // Safe parameter validation
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ?: '127.0.0.1';
        
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, created_at) VALUES (:uid, :action, :ip, NOW())");
        $stmt->execute([
            'uid'    => $userId,
            'action' => trim($action),
            'ip'     => $ipAddress
        ]);
    } catch (Throwable $e) {
        // Intercept pipeline exceptions silently to prevent critical frontend customer breaks
        error_log("Audit logging engine break: " . $e->getMessage());
    }
}