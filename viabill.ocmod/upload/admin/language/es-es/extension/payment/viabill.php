<?php
// Heading
$_['heading_title']          = 'Pagos ViaBill';

// Text
$_['text_extension']         = 'Extensiones';
$_['text_success']           = 'Éxito: ¡Has modificado el módulo de pagos ViaBill!';
$_['text_edit']              = 'Editar Pagos ViaBill';
$_['text_authorization']     = 'Solo Autorización';
$_['text_authorization_capture'] = 'Autorización y Captura';
$_['text_capture_success']   = '¡Pago capturado con éxito!';
$_['text_capture_error']     = 'Error al capturar el pago:';
$_['text_refund_success']    = '¡Pago reembolsado con éxito!';
$_['text_refund_error']      = 'Error al reembolsar el pago:';
$_['text_void_success']      = '¡Pago anulado con éxito!';
$_['text_void_error']        = 'Error al anular el pago:';
$_['text_all_zones']         = 'Todas las Zonas';
$_['text_enabled']           = 'Habilitado';
$_['text_disabled']          = 'Deshabilitado';

// Entry Labels (form fields)
$_['entry_api_key']          = 'Clave API de ViaBill';
$_['entry_secret_key']       = 'Clave Secreta de ViaBill';
$_['entry_test_mode']        = 'Modo de Pruebas';
$_['entry_transaction_mode'] = 'Tipo de Transacción';
$_['entry_transaction_mode_help'] = 'Elija si desea solo autorizar los pagos o autorizar y capturar inmediatamente.';
$_['entry_pricetag']         = 'Script de PriceTag de ViaBill';
$_['entry_pricetag_help']    = 'Pegue el script PriceTag completo proporcionado por ViaBill. Puede incluir las etiquetas script de apertura y cierre.';
$_['entry_authorize_order_status']     = 'Estado del Pedido (Tras Autorización)';
$_['entry_capture_order_status']      = 'Estado del Pedido (Tras Captura)';
$_['entry_status']           = 'Estado del Módulo';
$_['entry_hide_checkout']    = 'Ocultar en checkout';
$_['entry_sort_order']       = 'Orden de Aparición';
$_['entry_geo_zone']         = 'Zona Geográfica';

// Pricetags
$_['tab_general'] = 'Ajustes generales';
$_['tab_pricetag'] = 'Configuración PriceTag';
$_['entry_pricetag_common']  = 'Opciones Comunes de PriceTag';
$_['entry_pricetag_product']               = 'PriceTag en Página de Producto';
$_['entry_pricetag_product_label']         = 'Etiqueta de PriceTag';
$_['entry_pricetag_cart']               = 'PriceTag en Página del Carrito';
$_['entry_pricetag_cart_label']         = 'Etiqueta de PriceTag';
$_['entry_pricetag_checkout']               = 'PriceTag en Página de Pago';
$_['entry_pricetag_checkout_label']         = 'Etiqueta de PriceTag';

$_['entry_pricetag_language'] = 'Idioma';
$_['entry_pricetag_country'] = 'País';
$_['entry_pricetag_currency'] = 'Moneda';
$_['entry_pricetag_decimal_separator'] = 'Separador Decimal';

$_['entry_pricetag_product_dynamic_price'] = 'Selector de Precio Dinámico';
$_['entry_pricetag_product_dynamic_price_trigger'] = 'Disparador de Precio Dinámico';
$_['entry_pricetag_product_dynamic_price_trigger_delay'] = 'Retraso del Disparador';
$_['entry_pricetag_product_append_selector'] = 'Selector para Añadir PriceTag';
$_['entry_pricetag_product_append_after'] = 'Añadir Después del Selector';

$_['entry_pricetag_cart_dynamic_price'] = 'Selector de Precio Dinámico';
$_['entry_pricetag_cart_dynamic_price_trigger'] = 'Disparador de Precio Dinámico';
$_['entry_pricetag_cart_dynamic_price_trigger_delay'] = 'Retraso del Disparador';
$_['entry_pricetag_cart_append_selector'] = 'Selector para Añadir PriceTag';
$_['entry_pricetag_cart_append_after'] = 'Añadir Después del Selector';

$_['entry_pricetag_checkout_dynamic_price'] = 'Selector de Precio Dinámico';
$_['entry_pricetag_checkout_dynamic_price_trigger'] = 'Disparador de Precio Dinámico';
$_['entry_pricetag_checkout_dynamic_price_trigger_delay'] = 'Retraso del Disparador';
$_['entry_pricetag_checkout_append_selector'] = 'Selector para Añadir PriceTag';
$_['entry_pricetag_checkout_append_after'] = 'Añadir Después del Selector';

$_['entry_pricetag_product_align'] = 'Alineación (Página de Producto)';
$_['entry_pricetag_product_width'] = 'Ancho (Página de Producto)';
$_['entry_pricetag_cart_align'] = 'Alineación (Carrito)';
$_['entry_pricetag_cart_width'] = 'Ancho (Carrito)';
$_['entry_pricetag_checkout_align'] = 'Alineación (Pago)';
$_['entry_pricetag_checkout_width'] = 'Ancho (Pago)';

// Credenciales de la cuenta (introducidas manualmente)
$_['text_credentials'] = 'Credenciales de la cuenta ViaBill';
$_['text_credentials_intro'] = 'Introduzca su clave API, secreto API y script PriceTag de ViaBill. Encontrará estos valores en su cuenta de comerciante de ViaBill. Los tres valores son necesarios para que la pasarela de pago y los PriceTags funcionen.';
$_['text_credentials_missing'] = 'Faltan las credenciales de su cuenta ViaBill. Introduzca su clave API, secreto API y script PriceTag en la sección "Credenciales de la cuenta ViaBill" a continuación.';
$_['help_secret_key_saved'] = 'Ya hay un secreto API guardado (oculto por seguridad). Deje este campo vacío para conservarlo.';

// Buttons
$_['button_save']            = 'Guardar';
$_['button_cancel']          = 'Cancelar';

// Error
$_['error_permission']       = 'Advertencia: ¡No tienes permiso para modificar los pagos ViaBill!';
$_['error_api_key']          = '¡La clave API es obligatoria!';
$_['error_secret_key']       = '¡La clave secreta es obligatoria!';

// Signup note, payment action messages and order history comments
$_['text_signup_title'] = '¿Aún no tiene las credenciales de su cuenta ViaBill?';
$_['text_signup_text'] = 'Complete el formulario de registro en línea de ViaBill para obtener su clave API, su secreto API y su script de PriceTag.';
$_['text_signup_link'] = 'Ir al formulario de registro de ViaBill';
$_['error_amount'] = 'Introduzca un importe válido mayor que 0.';
$_['error_no_transaction'] = 'No se ha encontrado ninguna transacción de ViaBill para este pedido.';
$_['error_capture_amount_range'] = 'El importe a capturar debe ser mayor que 0 y como máximo %s.';
$_['error_refund_amount_range'] = 'El importe a reembolsar debe ser mayor que 0 y como máximo %s.';
$_['error_void_captured'] = 'El pago ya se ha capturado (parcialmente) y ya no se puede anular. Utilice un reembolso en su lugar.';
$_['error_api_http'] = 'ViaBill respondió con HTTP %d.';
$_['error_api_unreachable'] = 'No se ha podido conectar con ViaBill.';
$_['text_history_captured'] = 'Pago de ViaBill capturado: %s %s';
$_['text_history_refunded'] = 'Pago de ViaBill reembolsado: %s %s';
$_['text_history_voided'] = 'Autorización de ViaBill anulada';
