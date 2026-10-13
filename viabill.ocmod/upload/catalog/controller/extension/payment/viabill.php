<?php
class ControllerExtensionPaymentViabill extends Controller {    
    /**
     * Index method renders the ViaBill checkout confirmation page with all the required template data.
     *
     * @return string HTML output from the view
     */
    public function index(): string {
        // Load the language file containing all necessary text strings.
        $this->load->language('extension/payment/viabill');
        
        // Prepare template data
        $data = [];
        
        // Logo – you can change the filename or make it a config value.
        $data['viabill_logo'] = 'via_logo.png';
        
        // Text labels from the language file
        $data['text_description'] = $this->language->get('text_description');
        $data['text_instruction'] = $this->language->get('text_instruction');
        $data['text_payment'] = $this->language->get('text_payment');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_test_mode_warning'] = $this->language->get('text_test_mode_warning');
        $data['text_redirecting'] = $this->language->get('text_redirecting');
        
        // Retrieve order details (assuming order_id is stored in session)
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/viabill');   

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
            $order_info = $this->model_checkout_order->getOrder($order_id);
            
            // Populate order-related variables for the view.
            $data['order_id'] = $order_id;
            $data['amount'] = $order_info['total'];
            $data['currency'] = $order_info['currency_code'];
        } else {
            $data['order_id'] = '';
            $data['amount'] = 0;
            $data['currency'] = '';
        }
        
        // Check if the module is operating in test mode
        $data['test_mode'] = $this->config->get('payment_viabill_test') ? true : false;
        
        // Define the action URL to which the confirm button will send the AJAX request.
        // This should point to your "confirm" method in this controller.
        $data['action'] = $this->url->link('extension/payment/viabill/confirm', '', true);
        
        // Render and return the view template
        return $this->load->view('extension/payment/viabill', $data);
    }
    
    /**
     * Confirm the order and initiate ViaBill payment.
     * This function is typically called when the customer clicks "Confirm Order".
     * It will send the checkout-authorize request to ViaBill and handle the redirect.
     */
    public function confirm(): void {
                        
        // Check if this is the correct payment method
        if (empty($this->session->data['order_id']) ||
            !isset($this->session->data['payment_method']['code']) ||
            $this->session->data['payment_method']['code'] != 'viabill') {
            $json['error'] = 'Invalid payment method';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Load language class
        $this->load->language('extension/payment/viabill');
        
        // Load necessary models
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/viabill');
        
        // Load helper classes
        $this->loadViabillHelpers(); 
                
        $registry = $this->registry;
        ViaBillServices::setRegistry($registry);
        
        // Get order info
        $order_id = (int)$this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            // If order not found (should not happen at this stage), redirect to checkout
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }        
        
        // Determine transaction type (auth or auth+capture) from config
        $authOnly = ($this->config->get('payment_viabill_transaction_mode') == 'authorize');
        
        // Build request data to send to ViaBill
        $apiKey    = $this->config->get('payment_viabill_api_key');
        $secret    = $this->config->get('payment_viabill_secret_key');
        $currency  = $order_info['currency_code'];
        $amount    = number_format($order_info['total'], 2, '.', '');  // total amount formatted as e.g. 100.00
        $transactionId = 'via-' . $order_id . '-' . ViaBillHelper::generateRandomString(6);  // Generate a unique transaction reference (via-<orderid>-<random>, random_int based)
        
        // Prepare customer info for customParams (e.g., name, email, phone)
        $address = empty($order_info['payment_address_1'])?$order_info['shipping_address_1']:$order_info['payment_address_1'];
        $city = empty($order_info['payment_city'])?$order_info['shipping_city']:$order_info['payment_city'];
        $postalCode = empty($order_info['payment_postcode'])?$order_info['shipping_postcode']:$order_info['payment_postcode'];
        $country = !empty($order_info['shipping_iso_code_2'])?strtolower($order_info['shipping_iso_code_2']):'';

        $customer = [            
            'email'=> $order_info['email'],
            'phoneNumber' => $order_info['telephone'],            
            'firstName' => $order_info['firstname'],
            'lastName' => $order_info['lastname'],
            'fullName' => trim($order_info['firstname'].' '.$order_info['lastname']),
            'address' => $address,
            'city' => $city,
            'postalCode' => $postalCode,
            'country' => $country
        ];        

        // Prepare cart info for cartParams (e.g., products in the order)
        $products = $this->cart->getProducts();
        $cartItems = [];
        foreach ($products as $product) {
            $cartItems[] = [
                'id'       => $product['product_id'],
                'name'     => $product['name'],
                'quantity' => $product['quantity'],
                'price'    => number_format($product['price'], 2, '.', '')
            ];
        }
        $cart = ['items' => $cartItems];
                
        // Determine environment
        $testMode = $this->config->get('payment_viabill_test') ? ViaBillConstants::TEST_MODE_ON : ViaBillConstants::TEST_MODE_OFF;
        
        // Try-Before-You-Buy flag (assuming not used by default)
        $tbybValue = ViaBillConstants::TBYB_OFF;          
        $platformValue = ViaBillConstants::AFFILIATE;  // Identify the platform (ViaBill may expect the platform name)

        // URLs
        $success_url = $this->url->link('checkout/success', '', true);
        $cancel_url = $this->url->link('extension/payment/viabill/cancel', 'order_id=' . $order_id, true);
        $callback_url = $this->url->link('extension/payment/viabill/callback', '', true);

        // Assemble data for POST to ViaBill's checkout-authorize API. The
        // sha256check checksum (protocol 3.1) is calculated from these values
        // by ViaBillServices::getEndPointData().
        $request_data = [
            'protocol'     => ViaBillConstants::PROTOCOL, 
            'order_number' => $order_id,
            'apikey'       => $apiKey,
            'secret'       => $secret,
            'transaction'  => $transactionId,
            'amount'       => $amount,
            'currency'     => $currency,
            'success_url'  => $success_url,
            'cancel_url'   => $cancel_url,
            'callback_url' => $callback_url,
            'test'         => $testMode,
            'customParams' => $customer,
            // 'cartParams'   => json_encode($cart),
            'tbyb'         => $tbybValue,
        ];

        // Remember when the first protocol 3.1 (SHA-256) checkout was sent, so
        // MD5-signed callbacks are only accepted for older transactions.
        $this->rememberSha256Switch();
        
        // Before redirecting, add an initial order history entry with "Pending" status (or a custom status like "Awaiting Payment")
        $pendingStatusId = (int)$this->config->get('config_order_status_id');  // default store pending status
        $this->model_checkout_order->addOrderHistory($order_id, $pendingStatusId, sprintf($this->language->get('text_history_initiated'), $transactionId), false);

        // The request body is built from the declared checkout fields only:
        // the API secret is used for the checksum but never sent.
        $checkout_endpoint = ViaBillServices::getEndPointData('checkout', $request_data);

        // Send the checkout-authorize request to ViaBill
        $viaResponse = $checkout_endpoint ? ViaBillHelper::sendApiRequest($checkout_endpoint['endpoint'], $checkout_endpoint['data'], $checkout_endpoint['method']) : [];

        if (!empty($viaResponse['redirect_url'])) {
            // Store the generated transaction ID in our database for reference (log it)
            $this->db->query("INSERT INTO `" . DB_PREFIX . "viabill_transaction` SET order_id = " . (int)$order_id . ", transaction_id = '" . $this->db->escape($transactionId) . "', amount = '" . $this->db->escape($amount) . "', currency = '" . $this->db->escape($currency) . "', `type` = 'bnpl', status = '" . $this->db->escape(ViaBillConstants::TX_STATUS_INITIATED) . "', date_added = NOW(), date_modified = NOW()");
            // Redirect the customer to ViaBill's payment page
            // $this->response->redirect($viaResponse['redirect_url']);
                
            $json = ['redirect' => $viaResponse['redirect_url']];                                    
            // Always set proper headers
            $this->response->addHeader('Content-Type: application/json');            
            // Make sure to encode the JSON properly
            $this->response->setOutput(json_encode($json));
        } else {
            // If no redirect URL returned or an error occurred, handle gracefully
            ViaBillHelper::log('Checkout request failed for order #' . $order_id . ' [HTTP ' . (int)(isset($viaResponse['http_code']) ? $viaResponse['http_code'] : 0) . ']: ' . (isset($viaResponse['message']) ? $viaResponse['message'] : (isset($viaResponse['body']) ? $viaResponse['body'] : '')), 'error');

            $json = [];
            $json['error'] = $this->language->get('error_payment_failed');
            
            // Always set proper headers
            $this->response->addHeader('Content-Type: application/json');            
            // Make sure to encode the JSON properly
            $this->response->setOutput(json_encode($json));            
        }
    }
        

    /**
     * Callback endpoint for ViaBill.
     * ViaBill will call this URL to notify of payment status (success, cancel, etc).
     * It should update the order status accordingly.
     */    
    public function callback(): void {
        // Load helper classes
        $this->loadViabillHelpers();

        ViaBillServices::setRegistry($this->registry);

        // Retrieve data from ViaBill, using various methods
        $callbackData = null;

        // Method 1: Try OpenCart's request object
        if (!empty($this->request->post)) {
            $callbackData = $this->request->post;
        }
        // Method 2: Try raw POST data
        else {
            $rawData = file_get_contents('php://input');

            if (!empty($rawData)) {
                // Try JSON decode
                $jsonData = json_decode($rawData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $callbackData = $jsonData;
                }
                // Try form-encoded data
                else {
                    parse_str($rawData, $callbackData);
                }
            }
            // Method 3: Fallback to $_POST
            else if (!empty($_POST)) {
                $callbackData = $_POST;
            }
        }

        if (empty($callbackData) || !is_array($callbackData)) {
            ViaBillHelper::log('Received a callback request with empty data', 'error');
            $this->callbackResponse(400, 'ERROR');
            return;
        }

        // All fields covered by the signature must be present as plain values.
        $fields = [];
        foreach (['transaction', 'orderNumber', 'amount', 'currency', 'status', 'time', 'signature'] as $field) {
            if (!isset($callbackData[$field]) || !is_scalar($callbackData[$field])) {
                ViaBillHelper::log('Received a callback request without the "' . $field . '" field', 'error');
                $this->callbackResponse(400, 'ERROR');
                return;
            }
            $fields[$field] = (string)$callbackData[$field]; // exactly as signed by ViaBill
        }

        $order_id = (int)$fields['orderNumber'];
        $transactionId = $fields['transaction'];
        $status = strtoupper($fields['status']);  // e.g., 'APPROVED', 'CANCELLED', 'REJECTED'

        // Verify that this is a valid callback request. Without a configured
        // secret the signature would be computable by anyone.
        $secret = (string)$this->config->get('payment_viabill_secret_key');

        $validation_arguments = [
            $fields['transaction'],
            $fields['orderNumber'],
            $fields['amount'],
            $fields['currency'],
            $fields['status'],
            $fields['time'],
            $secret
        ];

        $signature_string = implode('#', $validation_arguments);
        $received_signature = strtolower($fields['signature']);

        // Protocol 3.1: SHA-256. Callbacks for transactions started under
        // protocol 3.0 may still be MD5 signed; those are only accepted for
        // such older transactions (checked below, once the transaction is loaded).
        $isSha256Signature = hash_equals(ViaBillServices::hashSignature($signature_string), $received_signature);
        $isLegacyMd5Signature = !$isSha256Signature && hash_equals(md5($signature_string), $received_signature);

        if (trim($secret) === '' || !$order_id || $transactionId === '' || (!$isSha256Signature && !$isLegacyMd5Signature)) {
            ViaBillHelper::log('Received an invalid callback request [order: ' . $order_id . '][transaction: ' . $transactionId . '][status: ' . $status . ']', 'error');
            $this->callbackResponse(400, 'NOT VALID');
            return;
        }

        // Match the callback data with our stored transaction (to ensure it's valid)
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "viabill_transaction` WHERE `order_id` = '" . (int)$order_id . "' AND `transaction_id` = '" . $this->db->escape($transactionId) . "' LIMIT 1");

        if (!$query->num_rows) {
            ViaBillHelper::log('Callback for an unknown transaction [order: ' . $order_id . '][transaction: ' . $transactionId . ']', 'error');
            $this->callbackResponse(400, 'NOT VALID');
            return;
        }

        $transaction = $query->row;

        if ($isLegacyMd5Signature && !$this->isLegacyMd5CallbackAllowed($transaction)) {
            ViaBillHelper::log('Rejected an MD5-signed callback for a protocol 3.1 transaction [order: ' . $order_id . '][transaction: ' . $transactionId . ']', 'error');
            $this->callbackResponse(400, 'NOT VALID');
            return;
        }

        // The approved amount and currency must be the ones the order was placed for.
        $expectedAmount = number_format((float)$transaction['amount'], 2, '.', '');
        $callbackAmount = number_format((float)$fields['amount'], 2, '.', '');

        if ($expectedAmount !== $callbackAmount ||
            ($fields['currency'] !== '' && strcasecmp($fields['currency'], (string)$transaction['currency']) !== 0)
        ) {
            ViaBillHelper::log('Callback amount/currency mismatch [order: ' . $order_id . '][expected: ' . $expectedAmount . ' ' . $transaction['currency'] . '][got: ' . $callbackAmount . ' ' . $fields['currency'] . ']', 'error');
            $this->callbackResponse(400, 'NOT VALID');
            return;
        }

        $this->load->model('checkout/order');
        $this->load->model('extension/payment/viabill');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info) {
            ViaBillHelper::log('Callback for a missing order #' . $order_id, 'error');
            $this->callbackResponse(400, 'NOT VALID');
            return;
        }

        $currentTxStatus = (string)$transaction['status'];
        $isPaid = ($currentTxStatus === ViaBillConstants::TX_STATUS_PAID);
        $isClosed = in_array($currentTxStatus, [ViaBillConstants::TX_STATUS_CANCELLED, ViaBillConstants::TX_STATUS_VOIDED], true);

        // Order history comments are in the store's default language (this
        // is a server-to-server request without a customer session).
        $this->load->language('extension/payment/viabill');

        $comment = '';
        $merchantNote = '';  // added as a separate entry, never sent to the customer
        $notifyCustomer = false;
        $newStatusId = (int)$order_info['order_status_id'];
        $newTxStatus = null;

        if ($status == 'APPROVED') {
            // Idempotency: this payment has already been processed.
            if ($isPaid) {
                $this->callbackResponse(200, 'OK');
                return;
            }

            // Never re-open an order that has been cancelled or voided.
            if ($isClosed) {
                ViaBillHelper::log('Ignoring APPROVED callback for cancelled/voided transaction [order: ' . $order_id . '][transaction: ' . $transactionId . ']', 'error');
                $this->callbackResponse(200, 'OK');
                return;
            }

            $notifyCustomer = true;
            $newTxStatus = ViaBillConstants::TX_STATUS_PAID;
            $authorizeStatusId = (int)($this->config->get('payment_viabill_authorize_order_status_id') ?: $this->config->get('config_order_status_id'));

            // Payment completed (or at least authorized)
            if ($this->config->get('payment_viabill_transaction_mode') == 'authorize') {
                $comment = $this->language->get('text_history_authorized');
                $newStatusId = $authorizeStatusId;
            } else {
                try {
                    $result = $this->model_extension_payment_viabill->capturePayment($transactionId, $expectedAmount, (string)$transaction['currency']);
                } catch (\Throwable $e) {
                    $result = ['success' => false, 'message' => $e->getMessage()];
                }

                if ($result['success']) {
                    $this->model_extension_payment_viabill->updateTransactionAmounts($order_id, (float)$expectedAmount, null);
                    $comment = $this->language->get('text_history_captured');
                    $newStatusId = (int)($this->config->get('payment_viabill_capture_order_status_id') ?: $this->config->get('config_order_status_id'));
                } else {
                    // The payment is approved (authorized) at ViaBill even
                    // though the automatic capture failed. The customer is
                    // told the payment succeeded; the capture failure is a
                    // note for the merchant only.
                    ViaBillHelper::log('Automatic capture failed for order #' . $order_id . ': ' . $result['message'], 'error');
                    $comment = $this->language->get('text_history_approved');
                    $merchantNote = $this->language->get('text_history_capture_failed');
                    $newStatusId = $authorizeStatusId;
                }
            }
        } elseif ($status == 'CANCELLED' || $status == 'REJECTED') {
            // Idempotency: already cancelled or voided.
            if ($isClosed) {
                $this->callbackResponse(200, 'OK');
                return;
            }

            // Never cancel an order whose payment has already been approved;
            // log it for manual review instead.
            if ($isPaid) {
                ViaBillHelper::log('Ignoring ' . $status . ' callback for an already approved transaction [order: ' . $order_id . '][transaction: ' . $transactionId . ']', 'error');
                $this->callbackResponse(200, 'OK');
                return;
            }

            $canceledStatusId = $this->getOrderStatusIdByName('Canceled');
            $newStatusId = (int)($canceledStatusId !== null ? $canceledStatusId : $this->config->get('config_order_status_id'));

            if ($status == 'CANCELLED') {
                $comment = $this->language->get('text_history_cancelled');
                $newTxStatus = ViaBillConstants::TX_STATUS_CANCELLED;
            } else {
                $comment = $this->language->get('text_history_rejected');
                $newTxStatus = ViaBillConstants::TX_STATUS_VOIDED;
            }
        } else {
            // Unknown status: record it without changing the order status.
            $comment = sprintf($this->language->get('text_history_status'), $status);
        }

        if ($newTxStatus !== null) {
            $this->db->query("UPDATE `" . DB_PREFIX . "viabill_transaction` SET `status` = '" . $this->db->escape($newTxStatus) . "', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "' AND `transaction_id` = '" . $this->db->escape($transactionId) . "'");
        }

        // Update OpenCart order history with the determined status and comment
        // (one entry; an order status of 0 would hide the order in the admin)
        if ($newStatusId) {
            $this->model_checkout_order->addOrderHistory($order_id, $newStatusId, $comment, $notifyCustomer);

            if ($merchantNote !== '') {
                $this->model_checkout_order->addOrderHistory($order_id, $newStatusId, $merchantNote, false);
            }
        }

        $this->callbackResponse(200, 'OK');
    }

    /**
     * Record the moment the module started sending protocol 3.1 (SHA-256)
     * checkouts, once. It is kept in its own setting, so saving the module
     * settings (which rewrites all payment_viabill_* settings) keeps it.
     */
    private function rememberSha256Switch(): void {
        if ($this->config->get(ViaBillConstants::SHA256_SINCE_SETTING_KEY)) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
            SELECT 0, '" . $this->db->escape(ViaBillConstants::SHA256_SINCE_SETTING_CODE) . "', '" . $this->db->escape(ViaBillConstants::SHA256_SINCE_SETTING_KEY) . "', '" . $this->db->escape($now) . "', 0
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "setting` WHERE `store_id` = 0 AND `key` = '" . $this->db->escape(ViaBillConstants::SHA256_SINCE_SETTING_KEY) . "')");

        $this->config->set(ViaBillConstants::SHA256_SINCE_SETTING_KEY, $now);
    }

    /**
     * Whether an MD5-signed (protocol 3.0) callback may be accepted for this
     * transaction: only for transactions started before the switch to
     * protocol 3.1, and only for a limited time after the switch.
     */
    private function isLegacyMd5CallbackAllowed(array $transaction): bool {
        $since = (string)$this->config->get(ViaBillConstants::SHA256_SINCE_SETTING_KEY);

        // No protocol 3.1 checkout has been sent yet: every transaction is a 3.0 one.
        if ($since === '' || !strtotime($since)) {
            return true;
        }

        $sinceTime = strtotime($since);

        if (time() > $sinceTime + ViaBillConstants::LEGACY_MD5_CALLBACK_DAYS * 86400) {
            return false;
        }

        // Transactions stored before the date columns were filled in are
        // necessarily older than the switch.
        $dateAdded = isset($transaction['date_added']) ? (string)$transaction['date_added'] : '';
        $addedTime = ($dateAdded === '' || strpos($dateAdded, '0000-00-00') === 0) ? false : strtotime($dateAdded);

        return $addedTime === false || $addedTime < $sinceTime;
    }

    /**
     * Send the answer to a ViaBill callback request.
     */
    private function callbackResponse(int $code, string $body): void {
        http_response_code($code);
        $this->response->setOutput($body);
    }

    /**
     * Cancel return endpoint.
     * This can handle when a customer cancels payment on ViaBill and is redirected back.
     * It could clean up or inform the user.
     */
    public function cancel(): void {
        // Maybe add a message that payment was cancelled and redirect to checkout page or cart.
        $this->load->language('extension/payment/viabill');
        $this->session->data['error'] = $this->language->get('error_cancelled');
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
    }

    // Or create a single loader method
    private function loadViabillHelpers() {
        require_once(DIR_SYSTEM . 'library/viabill/viabill_constants.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_services.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_helper.php');
    }
    
    /**
     * Event method to inject ViaBill PriceTag script into product pages.
     * Triggered by the event we registered on install (catalog/view/product/product/after).
     * It appends the PriceTag script HTML (if configured) to the page output.
     * 
     * @param string $route
     * @param mixed $data
     * @param string $output (by reference)
     */
    public function injectProductPriceTag(&$route, &$data, &$output): void {       
        // Check if we're on a product page
        if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {
            // Load your price tag content            
            $pricetagScript = '';
            $priceTagCSS = '';
            $pricetag_content = $this->config->get('payment_viabill_pricetag');			
			if (!empty($pricetag_content)) {                                                                                                        
                $config_prefix = 'payment_viabill_pricetag_product_';
                $config_common_prefix = 'payment_viabill_pricetag_';

                $dynamic_price = $this->config->get($config_prefix.'dynamic_price');                
                $dynamic_price_trigger = $this->config->get($config_prefix.'dynamic_price_trigger');
                $dynamic_price_trigger_delay = $this->config->get($config_prefix.'dynamic_price_trigger_delay');
                $decimal_separator = $this->config->get($config_common_prefix.'decimal_separator');
                $currency = !empty($this->config->get($config_common_prefix.'currency'))?$this->config->get($config_common_prefix.'currency'):$this->autoDetectCurrency($data);
                $country = !empty($this->config->get($config_common_prefix.'country'))?$this->config->get($config_common_prefix.'country'):$this->autoDetectCountry($data);
                $language = !empty($this->config->get($config_common_prefix.'language'))?$this->config->get($config_common_prefix.'language'):$this->autoDetectLanguage($data);
                $append_selector = !empty($this->config->get($config_prefix.'append_selector'))?$this->config->get($config_prefix.'append_selector'):'.price';
                $append_after = !empty($this->config->get($config_prefix.'append_after'))?true:false;
                $append_first = true;
                $alignment = $this->config->get($config_prefix.'align');
                $width = $this->config->get($config_prefix.'width');

                $price = $data['price'];
                if (!empty($dynamic_price)) {
                    $price = '';
                }

                $pricetag_params = [                    
                    'target' => 'product',
                    'price' => $price,
                    'dynamic_price' => $dynamic_price,
                    'decimal_separator' => $decimal_separator,
                    'trigger' => $dynamic_price_trigger,
                    'trigger_delay' => $dynamic_price_trigger_delay,
                    'currency' => $currency,
                    'country' => $country, 
                    'language' => $language,
                    'append_selector' => $append_selector,
                    'append_after' => $append_after,
                    'append_first' => $append_first,
                    'alignment' => $alignment,
                    'width' => $width
                ];               

                $pricetagScript = $this->getViabillPricetagScript($pricetag_params, str_replace(['&lt;','&gt;'],['<','>'],$pricetag_content));

                $priceTagCSS = $this->getViabillPricetagCSS($pricetag_params);
			}

            if (!empty($pricetagScript) && is_string($output)) {
                // Insert the script before </body> (assuming output is HTML)
                $output = str_replace('</body>', $priceTagCSS.$pricetagScript . '</body>', $output);
            }                        
        }
    }

    /**
     * Event method to inject ViaBill PriceTag script into cart pages.
     * Triggered by the event we registered on install (catalog/view/checkout/cart/after).
     * It appends the PriceTag script HTML (if configured) to the page output.
     * 
     * @param string $route
     * @param mixed $data
     * @param string $output (by reference)
     */
    public function injectCartPriceTag(&$route, &$data, &$output): void {                 
        // Check if we're on a product page
        if (isset($this->request->get['route']) && $this->request->get['route'] == 'checkout/cart') {
             // Load your price tag content            
             $pricetagScript = '';
             $priceTagCSS = '';
             $pricetag_content = $this->config->get('payment_viabill_pricetag');			
             if (!empty($pricetag_content)) {                                                                                                      
                 $config_prefix = 'payment_viabill_pricetag_cart_';
                 $config_common_prefix = 'payment_viabill_pricetag_';

                 $dynamic_price = $this->config->get($config_prefix.'dynamic_price');                 
                 $dynamic_price_trigger = $this->config->get($config_prefix.'dynamic_price_trigger');
                 $dynamic_price_trigger_delay = $this->config->get($config_prefix.'dynamic_price_trigger_delay');
                 $decimal_separator = $this->config->get($config_common_prefix.'decimal_separator');
                 $currency = !empty($this->config->get($config_common_prefix.'currency'))?$this->config->get($config_common_prefix.'currency'):$this->autoDetectCurrency($data);
                 $country = !empty($this->config->get($config_common_prefix.'country'))?$this->config->get($config_common_prefix.'country'):$this->autoDetectCountry($data);
                 $language = !empty($this->config->get($config_common_prefix.'language'))?$this->config->get($config_common_prefix.'language'):$this->autoDetectLanguage($data);
                 $append_selector = !empty($this->config->get($config_prefix.'append_selector'))?$this->config->get($config_prefix.'append_selector'):'.price';
                 $append_after = !empty($this->config->get($config_prefix.'append_after'))?true:false;
                 $append_first = true;
                 $alignment = $this->config->get($config_prefix.'align');
                 $width = $this->config->get($config_prefix.'width');
 
                 $price = $this->cart->getTotal();
                 if (!empty($dynamic_price)) {
                     $price = '';
                 }
 
                 $pricetag_params = [                    
                     'target' => 'basket',
                     'price' => $price,
                     'dynamic_price' => $dynamic_price,
                     'decimal_separator' => $decimal_separator,
                     'trigger' => $dynamic_price_trigger,
                     'trigger_delay' => $dynamic_price_trigger_delay,
                     'currency' => $currency,
                     'country' => $country, 
                     'language' => $language,
                     'append_selector' => $append_selector,
                     'append_after' => $append_after,
                     'append_first' => $append_first,
                     'alignment' => $alignment,
                     'width' => $width
                 ];               
 
                 $pricetagScript = $this->getViabillPricetagScript($pricetag_params, str_replace(['&lt;','&gt;'],['<','>'],$pricetag_content));

                 $priceTagCSS = $this->getViabillPricetagCSS($pricetag_params);                  
             }
 
             if (!empty($pricetagScript) && is_string($output)) {
                 // Insert the script before </body> (assuming output is HTML)
                 $output = str_replace('</body>', $priceTagCSS.$pricetagScript . '</body>', $output);
             }                                               
        }
    }

    /**
     * Event method to inject ViaBill PriceTag script into checkout pages.
     * Triggered by the event we registered on install (catalog/view/checkout/checkout/after).
     * It appends the PriceTag script HTML (if configured) to the page output.
     * 
     * @param string $route
     * @param mixed $data
     * @param string $output (by reference)
     */
    public function injectCheckoutPriceTag(&$route, &$data, &$output) {                 
        // Check if ViaBill is enabled
        if (!$this->config->get('payment_viabill_status')) {
            return;
        }

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'checkout/checkout') {        
            // Get the price tag script from your configuration
            $pricetagScript = '';
            $priceTagCSS = '';
            $pricetag_content = $this->config->get('payment_viabill_pricetag');			
            if (!empty($pricetag_content)) {
                // Load your price tag content            
                $pricetagScript = '';
                $pricetag_content = $this->config->get('payment_viabill_pricetag');			
                if (!empty($pricetag_content)) {                                                                                                      
                    $config_prefix = 'payment_viabill_pricetag_checkout_';
                    $config_common_prefix = 'payment_viabill_pricetag_';

                    $dynamic_price = $this->config->get($config_prefix.'dynamic_price');                
                    $dynamic_price_trigger = $this->config->get($config_prefix.'dynamic_price_trigger');
                    $dynamic_price_trigger_delay = $this->config->get($config_prefix.'dynamic_price_trigger_delay');
                    $decimal_separator = $this->config->get($config_common_prefix.'decimal_separator');
                    $currency = !empty($this->config->get($config_common_prefix.'currency'))?$this->config->get($config_common_prefix.'currency'):$this->autoDetectCurrency($data);
                    $country = !empty($this->config->get($config_common_prefix.'country'))?$this->config->get($config_common_prefix.'country'):$this->autoDetectCountry($data);
                    $language = !empty($this->config->get($config_common_prefix.'language'))?$this->config->get($config_common_prefix.'language'):$this->autoDetectLanguage($data);
                    $append_selector = !empty($this->config->get($config_prefix.'append_selector'))?$this->config->get($config_prefix.'append_selector'):'.price';
                    $append_after = !empty($this->config->get($config_prefix.'append_after'))?true:false;
                    $append_first = true;
                    $alignment = $this->config->get($config_prefix.'align');
                    $width = $this->config->get($config_prefix.'width');

                    $price = $this->cart->getTotal();
                    if (!empty($dynamic_price)) {
                        $price = '';
                    }

                    $pricetag_params = [                    
                        'target' => 'payment',
                        'price' => $price,
                        'dynamic_price' => $dynamic_price,
                        'decimal_separator' => $decimal_separator,
                        'trigger' => $dynamic_price_trigger,
                        'trigger_delay' => $dynamic_price_trigger_delay,
                        'currency' => $currency,
                        'country' => $country, 
                        'language' => $language,
                        'append_selector' => $append_selector,
                        'append_after' => $append_after,
                        'append_first' => $append_first,
                        'alignment' => $alignment,
                        'width' => $width,                        
                    ];               

                    $pricetagScript = $this->getViabillPricetagScript($pricetag_params, str_replace(['&lt;','&gt;'],['<','>'],$pricetag_content));

                    $priceTagCSS = $this->getViabillPricetagCSS($pricetag_params);                
                }

                if (!empty($pricetagScript) && is_string($output)) {
                    // Insert the script before </body> (assuming output is HTML)
                    $output = str_replace('</body>', $priceTagCSS.$pricetagScript . '</body>', $output);
                }                 
            }
        
        }
    }
    
    private function getViabillPricetagScript(array $params, $priceTag_script): string {
        // 1) Build the <div> with all data- attributes
        // Only known views are used (the value also ends up in a class name).
        $target = in_array($params['target'] ?? '', ['product', 'basket', 'payment'], true) ? $params['target'] : 'product';

        $attribute_values = [
            'data-view'                        => $target,
            'data-price'                       => $params['price'] ?? '',
            'data-dynamic-price'               => $params['dynamic_price'] ?? '',
            'data-price-decimal-separator'     => $params['decimal_separator'] ?? '',
            'data-dynamic-price-triggers'      => $params['trigger'] ?? '',
            'data-dynamic-price-trigger-delay' => $params['trigger_delay'] ?? '',
            'data-currency'                    => $params['currency'] ?? '',
            'data-country-code'                => $params['country'] ?? '',
            'data-language'                    => $params['language'] ?? '',
        ];

        // Settings values are escaped for the HTML attribute; empty attributes are left out.
        $attrs = ['class="viabill-pricetag"'];
        foreach ($attribute_values as $name => $value) {
            $value = is_scalar($value) ? (string)$value : '';
            if ($value !== '') {
                $attrs[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        $alignment_wrapper_class = '';        
        switch ($params['alignment']) {
            case 'center':
            case 'right':
                $alignment_wrapper_class = 'viabill_wrapper_alignment_'.$params['alignment'];
                // $this->document->addStyle('catalog/view/theme/stylesheet/pricetag.css');
                break;
            default:
                // Do nothing.
        }        
  
        $pricetagDiv = '<div class="viabill-pricetag-'.$target.'-wrapper '.$alignment_wrapper_class.'"><div ' . implode(' ', $attrs) . '></div></div>';

        // Grab placement parameters, with safe defaults
        $appendSelector = (string)($params['append_selector'] ?? '.price');

        // Values placed inside the inline <script> are JSON encoded with all
        // HTML-significant characters escaped, so they cannot break out of the
        // string or the script element.
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
        $pricetagDivJs = json_encode($pricetagDiv, $jsonFlags);
        $appendSelectorJs = json_encode($appendSelector, $jsonFlags);
        $insertAfter    = !empty($params['append_after'])   ? 'true'  : 'false';
        $insertFirst    = !empty($params['append_first'])   ? 'true'  : 'false';

        // Clean and insert the pricetag
        $priceTag_script = str_replace(['<script>', '</script>'], ['',''], $priceTag_script);
  
        // 3) Build the JS
        $js = <<<JS
<script>
jQuery(function($){
    var pricetagHtml  = {$pricetagDivJs};
    var selector      = {$appendSelectorJs};
    var after         = {$insertAfter};
    var firstOnly     = {$insertFirst};

    // find the element(s) to attach to
    var \$targets = $(selector);
    if (!\$targets.length) return;

    // choose first or all
    var \$els = firstOnly ? \$targets.first() : \$targets;

    // insert
    if (after) {
    \$els.after(pricetagHtml);
    } else {
    \$els.before(pricetagHtml);
    }

    {$priceTag_script}
});
</script>
JS;
  
        return $js;
    }

    private function getViabillPricetagCSS(array $params): string {
        $css = <<<CSS
<style>
/* Align PriceTags to the right */
div.viabill_wrapper_alignment_right {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

div.viabill_wrapper_alignment_right .viabill-pricetag {
    display: flex !important;
    width: 300px;
    margin-left: auto;
}

div.viabill_wrapper_alignment_right .viabill-pricetag iframe.viabill {
    width: 100% !important;
}

/* Align PriceTags to the center */
div.viabill_wrapper_alignment_center {
    display: flex;
    justify-content: center;
    align-items: center;
}

div.viabill_wrapper_alignment_center .viabill-pricetag {
    display: flex !important;
    width: 300px;
}

div.viabill_wrapper_alignment_center .viabill-pricetag iframe.viabill {
    width: 100% !important;
}
</style>
CSS;    
        // Only a plain CSS length (e.g. "300px", "80%", "20em") is accepted as width.
        $target = in_array($params['target'] ?? '', ['product', 'basket', 'payment'], true) ? $params['target'] : 'product';
        $width = trim((string)($params['width'] ?? ''));

        if ($width !== '' && preg_match('/^\d+(\.\d+)?(px|%|em|rem|vw)$/i', $width)) {
            $css .= '<style> div.viabill-pricetag-'.$target.'-wrapper div.viabill-pricetag { width: '.$width.' !important;} </style>';
        }

        return $css;
    }

    private function autoDetectCurrency($data) {     
        if (isset($this->session->data['currency'])) {
            return $this->session->data['currency'];
        }
        return '';
    }

    private function autoDetectCountry($data) {
        return '';
    }

    private function autoDetectLanguage($data) {
        if (isset($data['language'])) {
            $parts = explode('-', $data['language']);
            if (!empty($parts[0])) {
                return strtoupper($parts[0]);
            }
        }
        return '';
    }
            
    // Helper: find an order status ID by name (similar to admin model)
    private function getOrderStatusIdByName(string $statusName): ?int {
        $query = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order_status WHERE name = '" . $this->db->escape($statusName) . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->num_rows ? (int)$query->row['order_status_id'] : null;
    }
}
?>
