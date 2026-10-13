<?php
// Heading
$_['heading_title']          = 'ViaBill Payments';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified the ViaBill Payments module!';
$_['text_edit']              = 'Edit ViaBill Payments';
$_['text_authorization']     = 'Authorization Only';
$_['text_authorization_capture'] = 'Authorization & Capture';
$_['text_capture_success']   = 'Payment captured successfully!';
$_['text_capture_error']     = 'Error capturing payment:';
$_['text_refund_success']    = 'Payment refunded successfully!';
$_['text_refund_error']      = 'Error refunding payment:';
$_['text_void_success']      = 'Payment voided successfully!';
$_['text_void_error']        = 'Error voiding payment:';
$_['text_all_zones']         = 'All Zones';
$_['text_enabled']           = 'Enabled';
$_['text_disabled']          = 'Disabled';

// Entry Labels (form fields)
$_['entry_api_key']          = 'ViaBill API Key';
$_['entry_secret_key']       = 'ViaBill Secret Key';
$_['entry_test_mode']        = 'Test Mode';
$_['entry_transaction_mode'] = 'Transaction Type';
$_['entry_transaction_mode_help'] = 'Choose whether to authorize payments only, or authorize and capture immediately.';
$_['entry_pricetag']         = 'ViaBill PriceTag Script';
$_['entry_pricetag_help']    = 'Paste the whole PriceTag script snippet provided by ViaBill. You may include the opening and closing script tags.';
$_['entry_authorize_order_status']     = 'Order Status (After Payment Authorization)';
$_['entry_capture_order_status']     = 'Order Status (After Payment Capture)';
$_['entry_status']           = 'Module Status';
$_['entry_hide_checkout']    = 'Hide in checkout';
$_['entry_sort_order']       = 'Sort Order';
$_['entry_geo_zone']         = 'Geo Zone';

// Pricetags
$_['tab_general'] = 'General Settings';
$_['tab_pricetag'] = 'PriceTag Settings';
$_['entry_pricetag_common']  = 'Common PriceTag Options';
$_['entry_pricetag_product']               = 'Product Page PriceTag';
$_['entry_pricetag_product_label']         = 'PriceTag Label';
$_['entry_pricetag_cart']               = 'Cart Page PriceTag';
$_['entry_pricetag_cart_label']         = 'PriceTag Label';
$_['entry_pricetag_checkout']               = 'Checkout Page PriceTag';
$_['entry_pricetag_checkout_label']         = 'PriceTag Label';

$_['entry_pricetag_language'] = 'Language';
$_['entry_pricetag_country'] = 'Country';
$_['entry_pricetag_currency'] = 'Currency';
$_['entry_pricetag_decimal_separator'] = 'Decimal Separator';

$_['entry_pricetag_product_dynamic_price'] = 'Dynamic Price Selector';
$_['entry_pricetag_product_dynamic_price_trigger'] = 'Dynamic Price Trigger';
$_['entry_pricetag_product_dynamic_price_trigger_delay'] = 'Trigger Delay';
$_['entry_pricetag_product_append_selector'] = 'Append PriceTag Selector';
$_['entry_pricetag_product_append_after'] = 'Append After Selector';

$_['entry_pricetag_cart_dynamic_price'] = 'Dynamic Price Selector';
$_['entry_pricetag_cart_dynamic_price_trigger'] = 'Dynamic Price Trigger';
$_['entry_pricetag_cart_dynamic_price_trigger_delay'] = 'Trigger Delay';
$_['entry_pricetag_cart_append_selector'] = 'Append PriceTag Selector';
$_['entry_pricetag_cart_append_after'] = 'Append After Selector';

$_['entry_pricetag_checkout_dynamic_price'] = 'Dynamic Price Selector';
$_['entry_pricetag_checkout_dynamic_price_trigger'] = 'Dynamic Price Trigger';
$_['entry_pricetag_checkout_dynamic_price_trigger_delay'] = 'Trigger Delay';
$_['entry_pricetag_checkout_append_selector'] = 'Append PriceTag Selector';
$_['entry_pricetag_checkout_append_after'] = 'Append After Selector';

$_['entry_pricetag_product_align'] = 'Alignment (Product Page)';
$_['entry_pricetag_product_width'] = 'Width (Product Page)';
$_['entry_pricetag_cart_align'] = 'Alignment (Cart Page)';
$_['entry_pricetag_cart_width'] = 'Width (Cart Page)';
$_['entry_pricetag_checkout_align'] = 'Alignment (Checkout Page)';
$_['entry_pricetag_checkout_width'] = 'Width (Checkout Page)';


// Account credentials (entered manually)
$_['text_credentials'] = 'ViaBill Account Credentials';
$_['text_credentials_intro'] = 'Enter your ViaBill API key, API secret and PriceTag script. You can find these values in your ViaBill merchant account. All three values are required for the payment gateway and the PriceTags to work.';
$_['text_credentials_missing'] = 'Your ViaBill account credentials are missing. Please enter your API key, API secret and PriceTag script in the "ViaBill Account Credentials" section below.';
$_['help_secret_key_saved'] = 'An API secret is already saved (hidden for security). Leave this field empty to keep it.';

// Buttons
$_['button_save']            = 'Save';
$_['button_cancel']          = 'Cancel';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify ViaBill Payments!';
$_['error_api_key']          = 'API Key is required!';
$_['error_secret_key']       = 'Secret Key is required!';

// Signup note, payment action messages and order history comments
$_['text_signup_title'] = 'No ViaBill account credentials yet?';
$_['text_signup_text'] = 'Fill in the ViaBill online signup form to get your API key, API secret and PriceTag script.';
$_['text_signup_link'] = 'Go to the ViaBill signup form';
$_['error_amount'] = 'Please enter a valid amount greater than 0.';
$_['error_no_transaction'] = 'No ViaBill transaction was found for this order.';
$_['error_capture_amount_range'] = 'The capture amount must be greater than 0 and at most %s.';
$_['error_refund_amount_range'] = 'The refund amount must be greater than 0 and at most %s.';
$_['error_void_captured'] = 'The payment has already been (partly) captured and can no longer be voided. Use a refund instead.';
$_['error_api_http'] = 'ViaBill answered with HTTP %d.';
$_['error_api_unreachable'] = 'ViaBill could not be reached.';
$_['text_history_captured'] = 'ViaBill payment captured: %s %s';
$_['text_history_refunded'] = 'ViaBill payment refunded: %s %s';
$_['text_history_voided'] = 'ViaBill authorization voided';
