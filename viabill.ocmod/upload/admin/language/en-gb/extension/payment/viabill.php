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

// Legal note (credit warning)
$_['text_legal_note'] = 'Legal Note';
$_['text_legal_note_intro'] = 'The legal note is a short information text shown to customers at checkout when ViaBill is selected as the payment method, such as a warning that borrowing money costs money.';
$_['entry_legal_note_status'] = 'Status';
$_['entry_legal_note_text'] = 'Text';
$_['help_legal_note_text'] = 'Leave empty to use the default legal note, in the language selected below.';
$_['text_legal_note_default'] = 'Default legal note:';
$_['entry_legal_note_language'] = 'Language';
$_['help_legal_note_language'] = 'Language of the default legal note. "Auto" uses the language of the customer.';
$_['text_legal_note_language_auto'] = 'Auto';
$_['text_legal_note_language_en'] = 'English';
$_['text_legal_note_language_da'] = 'Danish';
$_['text_legal_note_language_es'] = 'Spanish';

// Legal note styling
$_['entry_legal_note_font_size'] = 'Font size';
$_['help_legal_note_font_size'] = 'Font size of the legal note, e.g. 14px, 0.9em or 90%. Leave empty to use the font size of your theme.';
$_['entry_legal_note_font_style'] = 'Font style';
$_['text_legal_note_font_style_normal'] = 'Normal';
$_['text_legal_note_font_style_bold'] = 'Bold';
$_['text_legal_note_font_style_italic'] = 'Italic';
$_['text_legal_note_font_style_bold_italic'] = 'Bold italic';
$_['entry_legal_note_color'] = 'Text color';
$_['help_legal_note_color'] = 'Color code of the legal note text, e.g. #cc0000. Leave empty to use the text color of your theme.';
$_['entry_legal_note_align'] = 'Text alignment';
$_['text_legal_note_theme_default'] = 'Theme default';
$_['text_legal_note_align_left'] = 'Left';
$_['text_legal_note_align_center'] = 'Center';
$_['text_legal_note_align_right'] = 'Right';
$_['entry_legal_note_extra_css'] = 'Extra CSS';
$_['help_legal_note_extra_css'] = 'Optional CSS declarations applied to the legal note box, separated by semicolons (no selectors or braces), e.g. padding: 8px 12px; background-color: #fff8e1; border-left: 3px solid #f0b849; They are applied after the options above, so they can also override them.';
$_['error_legal_note_font_size'] = 'The font size of the legal note is not valid. Use a value such as 14px, 0.9em or 90%.';
$_['error_legal_note_color'] = 'The text color of the legal note is not valid. Use a color code such as #cc0000.';

// Legal note margins and read-only text
$_['help_legal_note_text_readonly'] = 'The legal note shown at checkout, in the language selected below.';
$_['entry_legal_note_margin_top'] = 'Margin top';
$_['help_legal_note_margin_top'] = 'Space above the legal note, e.g. 4px or 0.5em. A negative value moves it closer to the content above.';
$_['entry_legal_note_margin_bottom'] = 'Margin bottom';
$_['help_legal_note_margin_bottom'] = 'Space below the legal note, e.g. 4px or 0.5em.';
$_['entry_legal_note_margin_left'] = 'Margin left';
$_['help_legal_note_margin_left'] = 'Space to the left of the legal note, e.g. 4px. A negative value moves it to the left.';
$_['error_legal_note_margin'] = 'A margin of the legal note is not valid. Use a value such as 4px, -12px or 0.5em.';
