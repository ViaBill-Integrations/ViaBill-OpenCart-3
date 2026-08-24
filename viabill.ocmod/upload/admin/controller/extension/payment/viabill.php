<?php
class ControllerExtensionPaymentViabill extends Controller {        
    private $error = array(); // This is used to set the errors, if any.

    // Display the configuration form
    public function index(): void {
        // Load language entries for labels and messages
        $this->load->language('extension/payment/viabill');

        $data = array();       
        
        // Or manually assign specific ones you need
        $language_keys = [
            'heading_title', 'button_cancel',
            'text_credentials', 'text_credentials_intro', 'text_credentials_missing',
            'help_secret_key_saved',
        ];
        
        foreach ($language_keys as $key) {
            $data[$key] = $this->language->get($key);
        } 

        // Add the token as a separate variable for forms
        $data['user_token'] = $this->session->data['user_token'];
                       
        // Set page title
        $this->document->setTitle($this->language->get('heading_title'));
        
        // Load model for settings and any installation if not already done
        $this->load->model('extension/payment/viabill');   
                
        $this->loadViabillHelpers();
        
        // If this is a POST request and the user has permission, save the settings
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/viabill')) {
            // Save settings via model
            $post_data = $this->request->post;

            // The API secret field is rendered as an empty password input so
            // the secret is never printed back to the page. An empty submitted
            // value therefore means "keep the currently stored secret".
            if (empty($post_data['payment_viabill_secret_key'])) {
                $post_data['payment_viabill_secret_key'] = $this->config->get('payment_viabill_secret_key') ?? '';
            }

            // Normalize the manually entered credentials.
            if (isset($post_data['payment_viabill_api_key'])) {
                $post_data['payment_viabill_api_key'] = trim((string) $post_data['payment_viabill_api_key']);
            }
            $post_data['payment_viabill_secret_key'] = trim((string) $post_data['payment_viabill_secret_key']);
            if (isset($post_data['payment_viabill_pricetag'])) {
                $post_data['payment_viabill_pricetag'] = trim((string) $post_data['payment_viabill_pricetag']);
            }

            $this->model_extension_payment_viabill->saveSettings($post_data);
            
            // Set a success message and redirect back to extension list
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment/viabill', 
                'user_token=' . $this->session->data['user_token'] . '&type=payment'));
        }
        
        // Prepare breadcrumb navigation
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 
                     'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 
                     'user_token=' . $this->session->data['user_token'] . '&type=payment')
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/viabill', 
                     'user_token=' . $this->session->data['user_token'])
        ];
        
        // Form action and cancel URLs
        $data['save_action'] = $this->url->link('extension/payment/viabill', 
                                'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('marketplace/extension', 
                               'user_token=' . $this->session->data['user_token'] . '&type=payment');

        // Load list of order statuses for the dropdown
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        // Set default values by name
        $default_authorized_order_status = $this->getOrderStatusIdByName('Processing', $data['order_statuses']) ?? 0;
        $default_captured_order_status = $this->getOrderStatusIdByName('Complete', $data['order_statuses']) ?? 0;
        
        // Load existing configuration values (or defaults if not set)
        $data['payment_viabill_status'] = $this->config->get('payment_viabill_status') ?? 0;
        $data['payment_viabill_api_key'] = $this->config->get('payment_viabill_api_key') ?? '';
        $data['payment_viabill_secret_key'] = $this->config->get('payment_viabill_secret_key') ?? '';
        $data['payment_viabill_test'] = $this->config->get('payment_viabill_test') ?? 0;
        $data['payment_viabill_transaction_mode'] = $this->config->get('payment_viabill_transaction_mode') ?? 'authorize_capture';
        $data['payment_viabill_pricetag'] = $this->config->get('payment_viabill_pricetag') ?? '';
        $data['payment_viabill_authorize_order_status_id'] = $this->config->get('payment_viabill_authorize_order_status_id') ?? $default_authorized_order_status;
        $data['payment_viabill_capture_order_status_id'] = $this->config->get('payment_viabill_capture_order_status_id') ?? $default_captured_order_status;
        $data['payment_viabill_sort_order'] = $this->config->get('payment_viabill_sort_order') ?? 0;

        $data['payment_viabill_hide_checkout'] = $this->config->get('payment_viabill_hide_checkout') ?? 0;        

        // Add Geo Zone configuration
        $data['payment_viabill_geo_zone_id'] = $this->config->get('payment_viabill_geo_zone_id') ?? 0;

        // Labels for each view
        $data['entry_pricetag_product_label']          = $this->language->get('entry_pricetag_product_label');
        $data['entry_pricetag_cart_label']             = $this->language->get('entry_pricetag_cart_label');
        $data['entry_pricetag_checkout_label']         = $this->language->get('entry_pricetag_checkout_label');

        // Values (defaults)
        $data['payment_viabill_pricetag_product_label']  = $this->config->get('payment_viabill_pricetag_product_label')  ?? '';
        $data['payment_viabill_pricetag_cart_label']     = $this->config->get('payment_viabill_pricetag_cart_label')     ?? '';
        $data['payment_viabill_pricetag_checkout_label'] = $this->config->get('payment_viabill_pricetag_checkout_label') ?? '';

        // Check if the account credentials have been entered
        $api_key_exists = !empty($data['payment_viabill_api_key']);
        $data['api_key_exists'] = $api_key_exists;

        $secret_key_exists = !empty($data['payment_viabill_secret_key']);
        $data['secret_key_exists'] = $secret_key_exists;

        $data['credentials_missing'] = !$api_key_exists || !$secret_key_exists || empty($data['payment_viabill_pricetag']);

        // The API secret is never printed back to the page; the template
        // renders a masked password field that can be left empty to keep
        // the currently stored value.
        unset($data['payment_viabill_secret_key']);

        // Load geo zones for the dropdown
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        
        // Add "All Zones" option for the geo zone dropdown
        array_unshift($data['geo_zones'], [
            'geo_zone_id' => 0,
            'name' => $this->language->get('text_all_zones')
        ]);                  
        
        // Common PriceTag configuration
        $data['payment_viabill_pricetag_language']          = $this->config->get('payment_viabill_pricetag_language')          ?? 'auto';
        $data['payment_viabill_pricetag_country']           = $this->config->get('payment_viabill_pricetag_country')           ?? 'auto';
        $data['payment_viabill_pricetag_currency']          = $this->config->get('payment_viabill_pricetag_currency')          ?? 'auto';
        $data['payment_viabill_pricetag_decimal_separator'] = $this->config->get('payment_viabill_pricetag_decimal_separator') ?? 'auto';

        // Product page PriceTag configuration
        $data['payment_viabill_pricetag_product_dynamic_price']               = $this->config->get('payment_viabill_pricetag_product_dynamic_price')               ?? '';
        $data['payment_viabill_pricetag_product_dynamic_price_trigger']       = $this->config->get('payment_viabill_pricetag_product_dynamic_price_trigger')       ?? '';
        $data['payment_viabill_pricetag_product_dynamic_price_trigger_delay'] = $this->config->get('payment_viabill_pricetag_product_dynamic_price_trigger_delay') ?? 0;
        $data['payment_viabill_pricetag_product_append_selector']             = $this->config->get('payment_viabill_pricetag_product_append_selector')             ?? '';
        $data['payment_viabill_pricetag_product_append_after']                = $this->config->get('payment_viabill_pricetag_product_append_after')                ?? 1;
        $data['payment_viabill_pricetag_product_align']                       = $this->config->get('payment_viabill_pricetag_product_align') ?? 'default';
        $data['payment_viabill_pricetag_product_width']                       = $this->config->get('payment_viabill_pricetag_product_width') ?? '';

        // Cart page PriceTag configuration
        $data['payment_viabill_pricetag_cart_dynamic_price']               = $this->config->get('payment_viabill_pricetag_cart_dynamic_price')               ?? '';
        $data['payment_viabill_pricetag_cart_dynamic_price_trigger']       = $this->config->get('payment_viabill_pricetag_cart_dynamic_price_trigger')       ?? '';
        $data['payment_viabill_pricetag_cart_dynamic_price_trigger_delay'] = $this->config->get('payment_viabill_pricetag_cart_dynamic_price_trigger_delay') ?? 0;
        $data['payment_viabill_pricetag_cart_append_selector']             = $this->config->get('payment_viabill_pricetag_cart_append_selector')             ?? '';
        $data['payment_viabill_pricetag_cart_append_after']                = $this->config->get('payment_viabill_pricetag_cart_append_after')                ?? 1;
        $data['payment_viabill_pricetag_cart_align']                       = $this->config->get('payment_viabill_pricetag_cart_align') ?? 'default';
        $data['payment_viabill_pricetag_cart_width']                       = $this->config->get('payment_viabill_pricetag_cart_width') ?? '';

        // Checkout page PriceTag configuration
        $data['payment_viabill_pricetag_checkout_dynamic_price']               = $this->config->get('payment_viabill_pricetag_checkout_dynamic_price')               ?? '';
        $data['payment_viabill_pricetag_checkout_dynamic_price_trigger']       = $this->config->get('payment_viabill_pricetag_checkout_dynamic_price_trigger')       ?? '';
        $data['payment_viabill_pricetag_checkout_dynamic_price_trigger_delay'] = $this->config->get('payment_viabill_pricetag_checkout_dynamic_price_trigger_delay') ?? 0;
        $data['payment_viabill_pricetag_checkout_append_selector']             = $this->config->get('payment_viabill_pricetag_checkout_append_selector')             ?? '';
        $data['payment_viabill_pricetag_checkout_append_after']                = $this->config->get('payment_viabill_pricetag_checkout_append_after')                ?? 1;
        $data['payment_viabill_pricetag_checkout_align']                       = $this->config->get('payment_viabill_pricetag_checkout_align') ?? 'default';
        $data['payment_viabill_pricetag_checkout_width']                       = $this->config->get('payment_viabill_pricetag_checkout_width') ?? '';

        // Add any error or success messages from previous actions
        if (isset($this->session->data['viabill_error'])) {
            $data['error_warning'] = $this->session->data['viabill_error'];
            unset($this->session->data['viabill_error']);
        }
        
        if (isset($this->session->data['viabill_success'])) {
            $data['success'] = $this->session->data['viabill_success'];
            unset($this->session->data['viabill_success']);
        }
                
        // CSRF protection token and user token
        $data['user_token'] = $this->session->data['user_token'];
        
        // Common admin UI components
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // Always show the configuration form. The merchant obtains the API
        // key, API secret and PriceTag script elsewhere (e.g. from the
        // ViaBill merchant portal) and enters them manually in the "ViaBill
        // Account Credentials" section at the top of the form.
        $this->response->setOutput($this->load->view('extension/payment/viabill', $data));
    }

    // Get MyViaBill
    public function getMyViaBill(): string {
        $myViaBillURL = null;

        $this->loadViabillHelpers();        

        $key = $this->config->get('payment_viabill_api_key') ?? '';
        $secret = $this->config->get('payment_viabill_secret_key') ?? '';

        if (!empty($key) && !empty($secret)) {
            // Get myviabill endpoint
            $myviabill_endpoint = ViaBillServices::getApiEndPoint('myviabill');

            if (ViaBillConstants::PROTOCOL == '3.0') {                
                $signature = md5($key . '#' . $secret);
            } else {                
                $signature = hash('sha256', $key . '#' . $secret);
            }            

            // Prepare  data
            $myviabill_data = [
                'key' => $key,
                'signature' => $signature,
            ];            
                                        
            // Send registration request to ViaBill API            
            $response = ViaBillHelper::sendApiRequest($myviabill_endpoint['endpoint'], $myviabill_data, $myviabill_endpoint['method']);
                
            if (isset($response['status']) && $response['status'] === 'success') {
                // Extract API credentials from response
                $api_data = json_decode($response['body'], true);
				if (isset($api_data['url'])) {
					$myViaBillURL = $api_data['url'];
				}             
            }
        }    

        return $myViaBillURL;
    }

    // Get MyViaBill
    public function getViaBillNotifications(): array {
        $messages = [];

        $this->loadViabillHelpers();        

        $key = $this->config->get('payment_viabill_api_key') ?? '';
        $secret = $this->config->get('payment_viabill_secret_key') ?? '';

        if (!empty($key) && !empty($secret)) {
            // Get notifications endpoint
            $notifications_endpoint = ViaBillServices::getApiEndPoint('notifications');

            if (ViaBillConstants::PROTOCOL == '3.0') {                
                $signature = md5($key . '#' . $secret);
            } else {                
                $signature = hash('sha256', $key . '#' . $secret);
            }            

            // Prepare  data
            $notifications_data = [
                'key' => $key,
                'signature' => $signature,
            ];            
                                        
            // Send registration request to ViaBill API            
            $response = ViaBillHelper::sendApiRequest($notifications_endpoint['endpoint'], $notifications_data, $notifications_endpoint['method']);
                
            if (isset($response['status']) && $response['status'] === 'success') {
                // Extract API credentials from response
                $api_data = json_decode($response['body'], true);
				if (isset($api_data['messages'])) {
					$messages = $api_data['messages'];
				}          
            }
        }

        return $messages;
    }

    /**
     * Display order details for ViaBill transactions
     *
     * @return string The rendered HTML for the order details page
     */
    public function order(): string {
        // Load language entries for labels and messages        
        $this->load->language('extension/payment/viabill');

        // Load model class
        $this->load->model('extension/payment/viabill');

        // Load helper classes

        $order_id = $this->request->get['order_id'];

        // Fetch order details from the database or API
        $order_info = $this->model_extension_payment_viabill->getOrder($order_id);

        if (!$order_info) {
            return $this->load->view('error/not_found', ['error' => 'Order not found']);
        }

        // Prepare data for the template
        $data = [
            'order_id' => $order_id,
            'transaction_id' => $order_info['transaction_id'] ?? '',
            'order_total' => $order_info['total'],
            'currency' => $order_info['currency'],
            'captured_amount' => $order_info['captured_amount'] ?? 0,
            'refunded_amount' => $order_info['refunded_amount'] ?? 0,
            'remaining_capture' => $order_info['amount'] - ($order_info['captured_amount'] ?? 0),
            'remaining_refund' => ($order_info['captured_amount'] ?? 0) - ($order_info['refunded_amount'] ?? 0),            
            'capture_url' => $this->url->link('extension/payment/viabill/capture', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id),
            'refund_url' => $this->url->link('extension/payment/viabill/refund', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id),
            'void_url' => $this->url->link('extension/payment/viabill/void', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id),
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
        ];

        // Render the template with the prepared data
        return $this->load->view('extension/payment/viabill_order', $data);
    }

    /**
     * Capture payment for an authorized transaction
     */
    public function capture(): void {
        $this->load->language('extension/payment/viabill');
        $this->load->model('extension/payment/viabill');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/viabill')) {
            $order_id = $this->request->post['order_id'];
            $capture_amount = $this->request->post['capture_amount'];

            // Here you would call your API or service to capture the payment
            $result = $this->model_extension_payment_viabill->capturePayment($order_id, $capture_amount);

            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_capture_success');
                $this->model_extension_payment_viabill->updateTransactionAmounts($order_id, $capture_amount, null);
            } else {
                $this->session->data['error'] = $this->language->get('text_capture_error') . ' ' . $result['message'];
            }
        }

        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id));
    }

    /**
     * Refund payment for a captured transaction
     */
    public function refund(): void {
        $this->load->language('extension/payment/viabill');
        $this->load->model('extension/payment/viabill');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/viabill')) {
            $order_id = $this->request->post['order_id'];
            $refund_amount = $this->request->post['refund_amount'];

            // Here you would call your API or service to refund the payment
            $result = $this->model_extension_payment_viabill->refundPayment($order_id, $refund_amount);

            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_refund_success');
                $this->model_extension_payment_viabill->updateTransactionAmounts($order_id, null, $refund_amount);
            } else {
                $this->session->data['error'] = $this->language->get('text_refund_error') . ' ' . $result['message'];
            }
        }

        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id));
    }

    /**
     * Void an authorized transaction
     */
    public function void(): void {
        $this->load->language('extension/payment/viabill');
        $this->load->model('extension/payment/viabill');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/payment/viabill')) {
            $order_id = $this->request->post['order_id'];

            // Here you would call your API or service to void the payment
            $result = $this->model_extension_payment_viabill->voidPayment($order_id);

            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_void_success');
            } else {
                $this->session->data['error'] = $this->language->get('text_void_error') . ' ' . $result['message'];
            }
        }

        $this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id));
    }

    /**
    * Get the order status ID by its name.
    *
    * @param string $name The order status name to search for (e.g., "Processing")
    * @param array $order_statuses The list returned by getOrderStatuses()
    * @return int|null The matching order status ID or null if not found
    */
    private function getOrderStatusIdByName(string $name, array $order_statuses): ?int {
        foreach ($order_statuses as $status) {
            if (strcasecmp($status['name'], $name) === 0) {
                return (int)$status['order_status_id'];
            }
        }
        return null;
    }

    // Load helper files
    private function loadViabillHelpers() {
        require_once(DIR_SYSTEM . 'library/viabill/viabill_constants.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_services.php');
        require_once(DIR_SYSTEM . 'library/viabill/viabill_helper.php');
    }
    
    // Installation hook – called when installing the extension
    public function install(): void {
        $this->load->model('extension/payment/viabill');
        $this->model_extension_payment_viabill->install();  // e.g., create DB table, register events
    }
    
    // Uninstallation hook – called when uninstalling the extension
    public function uninstall(): void {
        $this->load->model('extension/payment/viabill');
        $this->model_extension_payment_viabill->uninstall();  // e.g., drop DB table, remove events
    }
    
}
?>
