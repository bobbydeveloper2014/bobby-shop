<?php
/**
 * Plugin Name: WooCommerce EMVCo QR Payment (VietQR Direct Image)
 * Description: Hiển thị mã QR VietQR bằng ảnh trực tiếp, không dùng API POST.
 * Version: 2.4
 * Author: BybyBank Dev
 */

if (!defined('ABSPATH')) {
    exit;
}

// Lấy ACQID theo bank code
function get_acq_id_from_bank_code($bank_code) {
    $mapping = [
        'ACB' => '970416',
        'VCB' => '970436',
        'TCB' => '970407',
        'MB'  => '970422',
        'BIDV'=> '970418',
        'VTB' => '970415',
        'TPB' => '970423',
        'VPB' => '970432',
        'VIB' => '970441',
        'HDB' => '970437',
        'SHB' => '970443',
        'MSB' => '970426',
        'OCB' => '970448',
        'EIB' => '970431',
        'SCB' => '970429',
        'ABB' => '970425',
        // Thêm nếu cần
    ];

    $bank_code = strtoupper($bank_code);
    return isset($mapping[$bank_code]) ? $mapping[$bank_code] : null;
}

// Ghép URL ảnh QR
function generate_vietqr_image_url($bank_code, $account, $amount, $order_number) {
    $acq_id = get_acq_id_from_bank_code($bank_code);
    if (!$acq_id) {
        return ['error' => 'Không xác định được ACQID cho bankCode: ' . $bank_code];
    }

    $template = 'compact2';
    $add_info = urlencode('ORDER' . $order_number);
    $url = "https://img.vietqr.io/image/{$acq_id}-{$account}-{$template}.png?amount={$amount}&addInfo={$add_info}";

    return ['qr' => $url];
}

// Hiển thị QR VietQR
function bybybank_display_vietqr_common($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $bacs_accounts = get_option('woocommerce_bacs_accounts');
    if (!is_array($bacs_accounts) || empty($bacs_accounts)) {
        echo "<p style='color:red;'>[VietQR] Không tìm thấy cấu hình tài khoản ngân hàng.</p>";
        return;
    }

    $bacs = $bacs_accounts[0];

    $account      = !empty($bacs['account_number']) ? $bacs['account_number'] : '1181186';
    $account_name = !empty($bacs['account_name']) ? $bacs['account_name'] : 'Hoàng Thị Thanh Huyền';
    $bank_code    = !empty($bacs['sort_code']) ? strtoupper($bacs['sort_code']) : 'ACB';

    if (!$account || !$account_name) {
        echo "<p style='color:red;'>[VietQR] Thiếu thông tin số tài khoản hoặc tên tài khoản.</p>";
        return;
    }

    $amount = intval($order->get_total());
    $order_number = $order->get_order_number();

    $result = generate_vietqr_image_url($bank_code, $account, $amount, $order_number);

    if (isset($result['error'])) {
        echo "<p style='color:red;'>[VietQR] Lỗi tạo mã QR: " . esc_html($result['error']) . "</p>";
        return;
    }

    $qr_image = $result['qr'];

    echo "<section class='woocommerce-order-vietqr'>";
    echo "<h2>Thanh toán bằng mã QR VietQR</h2>";
    echo "<p>Quét mã QR bên dưới để chuyển khoản nhanh chóng:</p>";
    echo "<img src='" . esc_url($qr_image) . "' alt='QR VietQR' style='max-width:300px; display:block; margin-top:10px;' />";
    echo "<p><strong>Số tiền:</strong> " . wc_price($amount) . "</p>";
    echo "<p><strong>Mã đơn hàng:</strong> #" . esc_html($order_number) . "</p>";
    echo "</section>";
}

// Hook cho cả thank you page và view order
add_action('woocommerce_thankyou', 'bybybank_display_vietqr_common', 10, 1);
add_action('woocommerce_view_order', 'bybybank_display_vietqr_common', 10, 1);
