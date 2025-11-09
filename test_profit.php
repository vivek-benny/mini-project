<?php
// Test the formatProfit function
function formatProfit($amount) {
    if ($amount >= 100000) {
        $lakhs = floor($amount / 100000);
        $remainder = $amount % 100000;
        if ($remainder > 0) {
            return $lakhs . 'L ' . number_format($remainder, 0);
        } else {
            return $lakhs . 'L';
        }
    } else {
        return '₹' . number_format($amount, 0);
    }
}

// Test cases
echo "Testing formatProfit function:\n";
echo "50000 -> " . formatProfit(50000) . "\n";      // Should show ₹50,000
echo "100000 -> " . formatProfit(100000) . "\n";    // Should show 1L
echo "150000 -> " . formatProfit(150000) . "\n";    // Should show 1L 50,000
echo "275000 -> " . formatProfit(275000) . "\n";    // Should show 2L 75,000
echo "1000000 -> " . formatProfit(1000000) . "\n";  // Should show 10L
?>