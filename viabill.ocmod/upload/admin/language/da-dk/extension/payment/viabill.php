<?php
// Heading
$_['heading_title']          = 'ViaBill Betalinger';

// Text
$_['text_extension']         = 'Udvidelser';
$_['text_success']           = 'Succes: Du har ændret ViaBill Betalingsmodulet!';
$_['text_edit']              = 'Rediger ViaBill Betalinger';
$_['text_authorization']     = 'Kun Godkendelse';
$_['text_authorization_capture'] = 'Godkendelse & Hævning';
$_['text_capture_success']   = 'Betaling blev hævet med succes!';
$_['text_capture_error']     = 'Fejl ved hævning af betaling:';
$_['text_refund_success']    = 'Betaling blev refunderet med succes!';
$_['text_refund_error']      = 'Fejl ved refundering af betaling:';
$_['text_void_success']      = 'Betaling blev annulleret med succes!';
$_['text_void_error']        = 'Fejl ved annullering af betaling:';
$_['text_all_zones']         = 'Alle Zoner';
$_['text_enabled']           = 'Aktiveret';
$_['text_disabled']          = 'Deaktiveret';

// Entry Labels (form fields)
$_['entry_api_key']          = 'ViaBill API-nøgle';
$_['entry_secret_key']       = 'ViaBill Secret';
$_['entry_test_mode']        = 'Testtilstand';
$_['entry_transaction_mode'] = 'Transaktionstype';
$_['entry_transaction_mode_help'] = 'Vælg om betalinger kun skal godkendes, eller også hæves med det samme.';
$_['entry_pricetag']         = 'ViaBill PriceTag Script';
$_['entry_pricetag_help']    = 'Indsæt hele PriceTag-scriptet fra ViaBill. Du må gerne inkludere de omsluttende script-tags.';
$_['entry_authorize_order_status']     = 'Ordrestatus (efter godkendelse)';
$_['entry_capture_order_status']     = 'Ordrestatus (efter hævning)';
$_['entry_status']           = 'Modulstatus';
$_['entry_hide_checkout']    = 'Skjul ved checkout';
$_['entry_sort_order']       = 'Sortering';
$_['entry_geo_zone']         = 'Geozone';

// Pricetags
$_['tab_general'] = 'Generelle indstillinger';
$_['tab_pricetag'] = 'PriceTag Indstillinger';
$_['entry_pricetag_common']  = 'Generalle PriceTag Indstillinger';
$_['entry_pricetag_product']               = 'Produktvisning PriceTag';
$_['entry_pricetag_product_label']         = 'PriceTag label';
$_['entry_pricetag_cart']               = 'Kurv PriceTag';
$_['entry_pricetag_cart_label']         = 'PriceTag label';
$_['entry_pricetag_checkout']               = 'Checkout PriceTag';
$_['entry_pricetag_checkout_label']         = 'PriceTag label';

$_['entry_pricetag_language'] = 'Sprog';
$_['entry_pricetag_country'] = 'Land';
$_['entry_pricetag_currency'] = 'Valuta';
$_['entry_pricetag_decimal_separator'] = 'Decimalseparator';

$_['entry_pricetag_product_dynamic_price'] = 'Dynamisk Prisselector';
$_['entry_pricetag_product_dynamic_price_trigger'] = 'Dynamisk Trigger';
$_['entry_pricetag_product_dynamic_price_trigger_delay'] = 'Trigger-forsinkelse';
$_['entry_pricetag_product_append_selector'] = 'Tilføj PriceTag Selector';
$_['entry_pricetag_product_append_after'] = 'Tilføj Efter Selector';

$_['entry_pricetag_cart_dynamic_price'] = 'Dynamisk Prisselektor';
$_['entry_pricetag_cart_dynamic_price_trigger'] = 'Dynamisk Trigger';
$_['entry_pricetag_cart_dynamic_price_trigger_delay'] = 'Trigger-forsinkelse';
$_['entry_pricetag_cart_append_selector'] = 'Tilføj PriceTag Selector';
$_['entry_pricetag_cart_append_after'] = 'Tilføj Efter Selector';

$_['entry_pricetag_checkout_dynamic_price'] = 'Dynamisk Prisselektor';
$_['entry_pricetag_checkout_dynamic_price_trigger'] = 'Dynamisk Trigger';
$_['entry_pricetag_checkout_dynamic_price_trigger_delay'] = 'Trigger-forsinkelse';
$_['entry_pricetag_checkout_append_selector'] = 'Tilføj PriceTag Selector';
$_['entry_pricetag_checkout_append_after'] = 'Tilføj Efter Selector';

$_['entry_pricetag_product_align'] = 'Justering (Produktvisning)';
$_['entry_pricetag_product_width'] = 'Bredde (Produktvisning)';
$_['entry_pricetag_cart_align'] = 'Justering (Kurv)';
$_['entry_pricetag_cart_width'] = 'Bredde (Kurv)';
$_['entry_pricetag_checkout_align'] = 'Justering (Checkout)';
$_['entry_pricetag_checkout_width'] = 'Bredde (Checkout)';

// Kontooplysninger (indtastes manuelt)
$_['text_credentials'] = 'ViaBill kontooplysninger';
$_['text_credentials_intro'] = 'Indtast din ViaBill API-nøgle, API-hemmelighed og PriceTag-script. Du finder disse værdier på din ViaBill-forhandlerkonto. Alle tre værdier er nødvendige, for at betalingsmodulet og PriceTags virker.';
$_['text_credentials_missing'] = 'Dine ViaBill kontooplysninger mangler. Indtast venligst din API-nøgle, API-hemmelighed og PriceTag-script i sektionen "ViaBill kontooplysninger" nedenfor.';
$_['help_secret_key_saved'] = 'En API-hemmelighed er allerede gemt (skjult af sikkerhedshensyn). Lad feltet være tomt for at beholde den.';

// Buttons
$_['button_save']            = 'Gem';
$_['button_cancel']          = 'Annuller';

// Error
$_['error_permission']       = 'Advarsel: Du har ikke tilladelse til at ændre ViaBill Betalinger!';
$_['error_api_key']          = 'API-nøgle er påkrævet!';
$_['error_secret_key']       = 'API Secret er påkrævet!';