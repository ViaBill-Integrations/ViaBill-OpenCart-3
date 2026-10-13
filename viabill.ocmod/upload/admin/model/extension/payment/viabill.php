<?php
class ModelExtensionPaymentViabill extends Model {
    
    // Save configuration settings to the database
    public function saveSettings(array $data): void {
        $this->load->model('setting/setting');
        // Save all settings for this payment module (prefix "payment_viabill")
        $this->model_setting_setting->editSetting('payment_viabill', $data);
    }
    
    // Set up necessary database table(s) or events on installation
    public function install(): void {
        // Create the database table for ViaBill transaction logs (if not exists)
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "viabill_transaction` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `transaction_id` VARCHAR(64) NOT NULL,
                `amount` DECIMAL(15,4) NOT NULL,
                `currency` VARCHAR(20) NOT NULL,
                `captured_amount` DECIMAL(15,4) NOT NULL DEFAULT 0,
                `refunded_amount` DECIMAL(15,4) NOT NULL DEFAULT 0,
                `type` VARCHAR(20) NOT NULL,                
                `status` varchar(20) NOT NULL DEFAULT 'INITIATED',
                `date_added` DATETIME NOT NULL,
                `date_modified` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        
        // Register an event to inject ViaBill PriceTag script on product pages (if needed)
        $this->load->model('setting/event');                

        // Remove any existing event with same code to avoid duplicates
        $this->model_setting_event->deleteEventByCode('viabill_product_pricetag');
        $this->model_setting_event->deleteEventByCode('viabill_cart_pricetag');
        $this->model_setting_event->deleteEventByCode('viabill_checkout_pricetag');

        // Register the event for product page, to display pricetags
        $this->model_setting_event->addEvent(
            'viabill_product_pricetag',
            'catalog/view/product/product/after',
            'extension/payment/viabill/injectProductPriceTag',
            1,
            1
        );

        // Register the event for cart page, to display pricetags
        $this->model_setting_event->addEvent(
            'viabill_cart_pricetag',
            'catalog/view/checkout/cart/after',
            'extension/payment/viabill/injectCartPriceTag',
            1,
            1
        );

        // Register the event for checkout page, to display pricetags
        $this->model_setting_event->addEvent(
            'viabill_checkout_pricetag',
            'catalog/view/checkout/checkout/after',
            'extension/payment/viabill/injectCheckoutPriceTag',
            1,
            1
        );

        // Add the proper permissions for the admin to access and edit Viabill Payments config page
        $this->load->model('user/user_group');

        // Get the current admin user group ID, typically the administrator group
        $user_group_id = $this->user->getGroupId();
        
        // Grant access and modify permission for this module’s route
        // (OpenCart 3 checks permissions per controller route, so this covers
        // all of the module's admin actions, e.g. order/capture/refund/void.)
        $this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/payment/viabill');
        $this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/payment/viabill');

        // No permissions on core routes (e.g. sale/order) are granted here:
        // access to orders stays as configured by the shop owner.
    }
    
    // Clean up on uninstallation
    public function uninstall(): void {
        $this->load->model('setting/event');
        // Remove the PriceTag injection event
        $this->model_setting_event->deleteEventByCode('viabill_product_pricetag');
        $this->model_setting_event->deleteEventByCode('viabill_cart_pricetag');
        $this->model_setting_event->deleteEventByCode('viabill_checkout_pricetag');

        // (Optional) Drop the transaction log table
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "viabill_transaction`;");

        // Optional
        // Αφαίρεση δικαιωμάτων από όλα τα user groups
        $this->load->model('user/user_group');
        $user_groups = $this->model_user_user_group->getUserGroups();
        
        foreach ($user_groups as $user_group) {
            $user_group_id = $user_group['user_group_id'];
            
            // More permissions
            $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill');
            $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill');
            $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill/auth');
            $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill/auth');
            $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill/order');
            $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill/order');
            $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill/login');
            $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill/login');
            $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill/register');
            $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill/register');

            // Entries added by earlier versions of the module
            foreach (['injectProductPriceTag', 'injectCartPriceTag', 'injectCheckoutPriceTag'] as $method) {
                $this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/payment/viabill/' . $method);
                $this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/payment/viabill/' . $method);
            }
        }
    }

    /**
     * Get ViaBill transaction details for an order
     *
     * @param int $order_id The order ID
     * @return array Transaction details
     */
    public function getOrder(int $order_id): array {
        // Query the viabill_transaction table to get transaction details
        $query = $this->db->query("SELECT vt.*, o.total 
            FROM `" . DB_PREFIX . "viabill_transaction` vt 
            LEFT JOIN `" . DB_PREFIX . "order` o ON (vt.order_id = o.order_id) 
            WHERE vt.order_id = '" . (int)$order_id . "' 
            LIMIT 1");

        if ($query->num_rows) {
            return $query->row;
        } else {
            // If no ViaBill transaction exists, get basic order info
            $this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($order_id);
            
            if ($order_info) {
                return [
                    'order_id' => $order_id,
                    'total' => $order_info['total'],
                    'amount' => $order_info['total'],
                    'currency' => $order_info['currency_code'],
                    'transaction_id' => '',
                    'captured_amount' => 0,
                    'refunded_amount' => 0
                ];
            }
            
            return [];
        }
    }
    
    // --- Admin Operations: Capture, Refund, Void ---
    /**
     * Capture a previously authorized ViaBill transaction.
     * @param int $order_id The OpenCart order ID to capture.
     * @param float|null $amount Amount to capture (null for the remaining amount).
     * @return array ['success' => bool, 'message' => string]
     */
    public function capturePayment(int $order_id, ?float $amount = null): array {
        $transaction = $this->getTransactionForOperation($order_id);
        if (!$transaction) {
            return $this->operationFailed($this->language->get('error_no_transaction'));
        }

        // Never capture more than what is still authorized.
        $remaining = round((float)$transaction['amount'] - (float)$transaction['captured_amount'], 2);
        $captureAmount = round($amount ?? $remaining, 2);

        if ($captureAmount <= 0 || $captureAmount > $remaining) {
            return $this->operationFailed(sprintf($this->language->get('error_capture_amount_range'), number_format($remaining, 2, '.', '')));
        }

        $response = ViaBillServices::request('capture_transaction', [
            'id'       => $transaction['transaction_id'],
            'apikey'   => (string)$this->config->get('payment_viabill_api_key'),
            'secret'   => (string)$this->config->get('payment_viabill_secret_key'), // used for the signature only
            // ViaBill expects capture amounts as negative values (also in the signature).
            'amount'   => number_format(-$captureAmount, 2, '.', ''),
            'currency' => $transaction['currency'],
        ]);

        if (!ViaBillHelper::isSuccessfulResponse($response)) {
            return $this->apiFailed('Capture', $order_id, $response);
        }

        $capturedStatusId = (int)($this->config->get('payment_viabill_capture_order_status_id') ?: $this->config->get('config_order_status_id'));
        $this->addOrderHistory($order_id, $capturedStatusId, sprintf($this->language->get('text_history_captured'), number_format($captureAmount, 2, '.', ''), $transaction['currency']), true);

        return ['success' => true, 'message' => '', 'amount' => $captureAmount];
    }

    /**
     * Refund a captured ViaBill transaction (partial or full).
     * @param int $order_id
     * @param float|null $amount Amount to refund (null for the remaining captured amount).
     * @return array ['success' => bool, 'message' => string]
     */
    public function refundPayment(int $order_id, ?float $amount = null): array {
        $transaction = $this->getTransactionForOperation($order_id);
        if (!$transaction) {
            return $this->operationFailed($this->language->get('error_no_transaction'));
        }

        // Never refund more than what has been captured and not yet refunded.
        $remaining = round((float)$transaction['captured_amount'] - (float)$transaction['refunded_amount'], 2);
        $refundAmount = round($amount ?? $remaining, 2);

        if ($refundAmount <= 0 || $refundAmount > $remaining) {
            return $this->operationFailed(sprintf($this->language->get('error_refund_amount_range'), number_format($remaining, 2, '.', '')));
        }

        $response = ViaBillServices::request('refund_transaction', [
            'id'       => $transaction['transaction_id'],
            'apikey'   => (string)$this->config->get('payment_viabill_api_key'),
            'secret'   => (string)$this->config->get('payment_viabill_secret_key'), // used for the signature only
            'amount'   => number_format($refundAmount, 2, '.', ''),
            'currency' => $transaction['currency'],
        ]);

        if (!ViaBillHelper::isSuccessfulResponse($response)) {
            return $this->apiFailed('Refund', $order_id, $response);
        }

        // Typically, you’d have a specific "Refunded" status ID configured or use OpenCart's "Refunded" if exists
        $refundedStatusId = (int)($this->getOrderStatusIdByName('Refunded') ?? $this->config->get('config_order_status_id'));
        $this->addOrderHistory($order_id, $refundedStatusId, sprintf($this->language->get('text_history_refunded'), number_format($refundAmount, 2, '.', ''), $transaction['currency']), true);

        return ['success' => true, 'message' => '', 'amount' => $refundAmount];
    }

    /**
     * Void a previously authorized ViaBill transaction (cancel before capture).
     * @param int $order_id
     * @return array ['success' => bool, 'message' => string]
     */
    public function voidPayment(int $order_id): array {
        $transaction = $this->getTransactionForOperation($order_id);
        if (!$transaction) {
            return $this->operationFailed($this->language->get('error_no_transaction'));
        }

        if ((float)$transaction['captured_amount'] > 0) {
            return $this->operationFailed($this->language->get('error_void_captured'));
        }

        $response = ViaBillServices::request('cancel_transaction', [
            'id'     => $transaction['transaction_id'],
            'apikey' => (string)$this->config->get('payment_viabill_api_key'),
            'secret' => (string)$this->config->get('payment_viabill_secret_key'), // used for the signature only
        ]);

        if (!ViaBillHelper::isSuccessfulResponse($response)) {
            return $this->apiFailed('Void', $order_id, $response);
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "viabill_transaction` SET `status` = '" . $this->db->escape(ViaBillConstants::TX_STATUS_VOIDED) . "', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "' AND `transaction_id` = '" . $this->db->escape($transaction['transaction_id']) . "'");

        // Typically, you’d have a specific "Cancelled" status ID configured or use OpenCart's "Cancelled" if exists
        $cancelStatusId = (int)($this->getOrderStatusIdByName('Voided') ?? $this->config->get('config_order_status_id'));
        $this->addOrderHistory($order_id, $cancelStatusId, $this->language->get('text_history_voided'), false);

        return ['success' => true, 'message' => ''];
    }

    /**
     * Load the helpers needed for API operations and return the order's
     * ViaBill transaction (empty array if there is none).
     */
    private function getTransactionForOperation(int $order_id): array {
        $this->load->language('extension/payment/viabill');

        $this->loadViabillHelpers();

        ViaBillServices::setRegistry($this->registry);

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "viabill_transaction` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `id` DESC LIMIT 1");

        return $query->num_rows ? $query->row : [];
    }

    private function operationFailed(string $message): array {
        return ['success' => false, 'message' => $message];
    }

    /**
     * Log a failed API operation and return a failure result. The response
     * body is logged, but never the request (it would contain the signature).
     */
    private function apiFailed(string $operation, int $order_id, array $response): array {
        $httpCode = isset($response['http_code']) ? (int)$response['http_code'] : 0;
        $details = isset($response['message']) ? $response['message'] : (isset($response['body']) ? $response['body'] : '');

        ViaBillHelper::log($operation . ' failed for order #' . $order_id . ' [HTTP ' . $httpCode . ']: ' . $details, 'error');

        return $this->operationFailed($httpCode ? sprintf($this->language->get('error_api_http'), $httpCode) : $this->language->get('error_api_unreachable'));
    }

    /**
     * Add an order-history record (same as catalog/model/checkout/order->addOrderHistory).
     */
    private function addOrderHistory(
        int   $order_id,
        int   $order_status_id,
        string $comment = '',
        bool  $notify = false,
        bool  $override = false
    ): void {
        // 1) Insert history record
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "order_history` SET
                `order_id`        = '" . (int)$order_id . "',
                `order_status_id` = '" . (int)$order_status_id . "',
                `notify`          = '" . ($notify ? 1 : 0) . "',
                `comment`         = '" . $this->db->escape($comment) . "',
                `date_added`      = NOW()"
        );

        // 2) Unless override is true, update the order’s current status
        if (!$override) {
            $this->db->query(
                "UPDATE `" . DB_PREFIX . "order` SET
                    `order_status_id` = '" . (int)$order_status_id . "',
                    `date_modified`   = NOW()
                WHERE `order_id`       = '" . (int)$order_id . "'"
            );
        }
    }
    
    // Helper: get ViaBill transaction ID stored for an order (e.g., from our log table)
    private function getViaBillTransactionId(int $order_id): ?string {
        $query = $this->db->query("SELECT `transaction_id` FROM `" . DB_PREFIX . "viabill_transaction` WHERE `order_id` = " . (int)$order_id);
        return $query->num_rows ? $query->row['transaction_id'] : null;
    }       

    /**
     * Update transaction amounts for an order
     *
     * @param int $order_id The order ID
     * @param float|null $captured_amount The amount to add to the captured total (or null to skip)
     * @param float|null $refunded_amount The amount to add to the refunded total (or null to skip)
     * @return void
     */
    public function updateTransactionAmounts(int $order_id, ?float $captured_amount, ?float $refunded_amount): void {
        $fields = [];

        if (!is_null($captured_amount)) {
            $fields[] = "`captured_amount` = `captured_amount` + " . (float)$captured_amount;
        }

        if (!is_null($refunded_amount)) {
            $fields[] = "`refunded_amount` = `refunded_amount` + " . (float)$refunded_amount;
        }

        // Always update the modification date
        $fields[] = "`date_modified` = NOW()";

        if (!empty($fields)) {
            $this->db->query("UPDATE `" . DB_PREFIX . "viabill_transaction` SET " . implode(', ', $fields) . " WHERE `order_id` = '" . (int)$order_id . "'");
        }
    }
    
    // Helper: find order status ID by name (for example "Refunded" or "Canceled")
    private function getOrderStatusIdByName(string $statusName): ?int {
        $query = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order_status WHERE name = '" . $this->db->escape($statusName) . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->num_rows ? (int)$query->row['order_status_id'] : null;
    } 

    // Load helper classes
    private function loadViabillHelpers() {
        require_once(DIR_SYSTEM . 'library/viabill/viabill_constants.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_services.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_helper.php');
    }
}
?>
