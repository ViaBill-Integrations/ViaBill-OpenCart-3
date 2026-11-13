<?php

/**
 * A helper class for various ViaBill utility methods.
 */
class ViaBillHelper { 

  /**
   * Format the value of the TBYB parameter.
   */
  public static function formatTbyb($tbyb) {
    if (empty($tbyb)) {
      return ViaBillConstants::TBYB_OFF;
    }
    elseif (($tbyb == 'true')||($tbyb == '1')||($tbyb == 1)) {
      return ViaBillConstants::TBYB_ON;
    }
    else {
      return ViaBillConstants::TBYB_OFF;
    }
  }

  /**
   * Get the TBYB value in the proper format.
   */
  public static function getFormattedTbyb($tbyb = null) {
    return self::formatTbyb($tbyb);
  }

  /**
   * Format the Test Mode value properly.
   */
  public static function formatTestMode($mode) {
    if (empty($mode)) {
      return ViaBillConstants::TEST_MODE_ON;
    }
    elseif (($mode == 'test')||($mode == 'true')||($mode == '1')||($mode == 1)) {
      return ViaBillConstants::TEST_MODE_ON;
    }
    else {
      return ViaBillConstants::TEST_MODE_OFF;
    }
  }

    
  /**
   * Get the ViaBill platform (affiliate) for this module.
   */
  public static function getViaBillApiPlatform() {
    return ViaBillConstants::AFFILIATE;
  }

  /**
   * Generate a random string for the payment transaction Id.
   */
  public static function generateRandomString($length = 10) {
    // Define the characters you want to include in the random string.
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    // Loop for the number of characters needed.
    for ($i = 0; $i < $length; $i++) {
      // Use random_int for better randomness and security.
      $index = random_int(0, $charactersLength - 1);
      $randomString .= $characters[$index];
    }

    return $randomString;
  }
  
  
  /**
   * Send a request to the specified URL with the given method and data.
   *
   * @param string $method_type The HTTP method to use (e.g., 'POST', 'GET')
   * @param string $url The URL to send the request to
   * @param array|string $data The data to send with the request
   * @param bool $auto_redirect Whether to automatically follow redirects
   * @return array An associative array containing the response details
   */
   public static function sendApiRequest($url, $data, $method_type = 'POST', $auto_redirect = false) {
      $ch = curl_init();             

      // Convert the payload array into JSON format for POST requests
      $jsonData = json_encode($data);          
  
      // Set the URL
      curl_setopt($ch, CURLOPT_URL, $url);      

      // Set the request method
      if (strtoupper($method_type) === 'POST') {          
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
      } else {
          // For GET requests, append data to URL if it's an array
          if (is_array($data)) {
              $url .= '?' . http_build_query($data);
              curl_setopt($ch, CURLOPT_URL, $url);
          }
      }

      // Return the transfer as a string instead of outputting it directly
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // Never wait indefinitely for the ViaBill API (it is called during
      // checkout), always verify its TLS certificate and only speak HTTPS.
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, ViaBillConstants::API_CONNECT_TIMEOUT);
      curl_setopt($ch, CURLOPT_TIMEOUT, ViaBillConstants::API_TIMEOUT);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
          curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
      }

      // Set headers for JSON if data is an array
      if (is_array($data)) {
          curl_setopt($ch, CURLOPT_HTTPHEADER, [            
            'Accept: */*',
            'User-Agent: ViaBill-OpenCart/1.0',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
          ]);
      }

      // Handle redirects
      if ($auto_redirect) {
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      } else {
          curl_setopt($ch, CURLOPT_HEADER, true); // Include header in the output
      }

      // Execute the request
      $response = curl_exec($ch);

      // Check for errors. The cURL handle is not closed with curl_close(): it
      // has no effect since PHP 8.0 and is deprecated since PHP 8.5. The
      // handle is freed when it goes out of scope.
      if (curl_errno($ch)) {
          $error = curl_error($ch);
          unset($ch);
          return [
              'status' => 'error',
              'message' => $error
          ];            
      }

      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $headers = substr($response, 0, $header_size);
      $body = substr($response, $header_size);

      unset($ch);

      // If not auto_redirecting, check for redirect in headers
      if (!$auto_redirect && preg_match('/^Location:\s*(\S+)\s*$/mi', $headers, $matches)) {
          return [
              'status' => 'redirect',
              'redirect_url' => trim($matches[1]),
              'http_code' => $http_code,
              'headers' => $headers,
              'body' => $body
          ];
      }

      return [
          'status' => 'success',
          'http_code' => $http_code,
          'headers' => $headers,
          'body' => $body
      ];
  }  
  
  /**
   * Log messages to OpenCart's error log or a custom ViaBill log file.
   *
   * @param string $message The message to log
   * @param string $level The log level ('info', 'error', 'debug', etc.)
   * @param bool $use_custom_log Whether to use a custom ViaBill log file instead of the default error log
   */
  public static function log($message, $level = 'info', $use_custom_log = true) {
    // Format the message with timestamp and level
    $formatted_message = '[' . strtoupper($level) . '] ' . $message;

    // Static context: use OpenCart 3's Log library directly, either the
    // ViaBill log file or OpenCart's default error log.
    $log = new \Log($use_custom_log ? 'viabill.log' : 'error.log');
    $log->write($formatted_message);
  }

  /**
   * Check whether a sendApiRequest() result is an actual success, i.e. the
   * ViaBill API answered with a 2xx status code (or, when $allow_redirect is
   * set, with a redirect). Transport errors and 4xx/5xx answers are failures.
   *
   * @param mixed $response The array returned by sendApiRequest()
   * @param bool $allow_redirect Whether a 3xx redirect counts as success
   * @return bool
   */
  public static function isSuccessfulResponse($response, $allow_redirect = false) {
    if (!is_array($response) || empty($response['status']) || empty($response['http_code'])) {
      return false;
    }

    $http_code = (int)$response['http_code'];

    if ($response['status'] === 'redirect') {
      return $allow_redirect && $http_code >= 300 && $http_code < 400;
    }

    return $response['status'] === 'success' && $http_code >= 200 && $http_code < 300;
  }

  /**
   * Default legal note (credit warning), used if the language file has none.
   */
  const LEGAL_NOTE_DEFAULT_TEXT = 'Warning! Borrow responsibly - it costs money.';

  /**
   * HTML tags the legal note may contain, e.g. a link to the terms.
   */
  const LEGAL_NOTE_ALLOWED_TAGS = '<a><b><strong><em><i><br><p><span>';

  /**
   * OpenCart language codes of the fixed legal note languages.
   */
  const LEGAL_NOTE_LANGUAGE_CODES = [
    'en' => 'en-gb',
    'da' => 'da-dk',
    'es' => 'es-es',
  ];

  /**
   * Get the default legal note from the extension's catalog language file of
   * the given language. The file is read directly, so the language does not
   * have to be installed in the shop.
   *
   * @param string $language_code OpenCart language code, e.g. 'da-dk'
   * @return string
   */
  public static function getLegalNoteDefaultText($language_code) {
    return self::getLegalNoteLanguageString($language_code, 'text_legal_note_default', self::LEGAL_NOTE_DEFAULT_TEXT);
  }

  /**
   * Get the "You are paying with" text shown before the ViaBill logo and the
   * legal note, in the given language.
   *
   * @param string $language_code OpenCart language code, e.g. 'da-dk'
   * @return string
   */
  public static function getLegalNotePayingWithText($language_code) {
    return self::getLegalNoteLanguageString($language_code, 'text_legal_note_paying_with', 'You are paying with');
  }

  /**
   * Get a text from the extension's catalog language file of the given
   * language. The file is read directly, so the language does not have to be
   * installed in the shop.
   *
   * @param string $language_code OpenCart language code, e.g. 'da-dk'
   * @param string $key Key in the language file
   * @param string $fallback Text if the file or the key is missing
   * @return string
   */
  private static function getLegalNoteLanguageString($language_code, $key, $fallback) {
    static $files = [];

    $language_code = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$language_code));

    if (!isset($files[$language_code])) {
      $files[$language_code] = [];
      // DIR_CATALOG is only defined in the admin.
      $catalog_dir = defined('DIR_CATALOG') ? DIR_CATALOG : DIR_APPLICATION;
      $file = $catalog_dir . 'language/' . $language_code . '/extension/payment/viabill.php';

      if ($language_code !== '' && is_file($file)) {
        // Language files fill a local $_ array.
        $_ = [];
        require $file;
        $files[$language_code] = $_;
      }
    }

    return !empty($files[$language_code][$key]) ? (string)$files[$language_code][$key] : $fallback;
  }

  /**
   * Get the language code to show the default legal note in.
   *
   * @param string $setting 'auto', 'en', 'da' or 'es'
   * @param string $current_code OpenCart code of the current language, used for 'auto'
   * @return string
   */
  public static function getLegalNoteLanguageCode($setting, $current_code) {
    return isset(self::LEGAL_NOTE_LANGUAGE_CODES[$setting]) ? self::LEGAL_NOTE_LANGUAGE_CODES[$setting] : (string)$current_code;
  }

  /**
   * Keep only simple, safe HTML in a legal note entered by the merchant.
   *
   * @param string $text
   * @return string
   */
  public static function sanitizeLegalNote($text) {
    $text = strip_tags((string)$text, self::LEGAL_NOTE_ALLOWED_TAGS);
    // Remove event handler attributes and javascript: links.
    $text = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $text);
    $text = preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '$1="#"', $text);

    return trim($text);
  }

  /**
   * Default font style of the legal note: the same as the order summary.
   */
  const LEGAL_NOTE_DEFAULT_FONT_STYLE = 'normal';

  /**
   * Default text alignment of the legal note: right, next to the
   * "Confirm Order" button.
   */
  const LEGAL_NOTE_DEFAULT_ALIGN = 'right';

  /**
   * Default margins of the legal note. They keep the spacing the legal note
   * always had (0.5em above and below) and are positive, so safe for every
   * theme.
   */
  const LEGAL_NOTE_DEFAULT_MARGINS = [
    'top' => '0.5em',
    'bottom' => '0.5em',
    'left' => '0px',
  ];

  /**
   * Get a valid CSS margin, or empty string. A plain number is taken as
   * pixels, and negative values are allowed.
   *
   * @param mixed $value Value entered by the merchant
   * @return string
   */
  public static function sanitizeLegalNoteMargin($value) {
    $value = is_string($value) ? strtolower(trim($value)) : '';
    $value = str_replace(',', '.', $value);

    if (!preg_match('/^(-?)(\d+(?:\.\d+)?|\.\d+)\s*(px|em|rem|pt|%)?$/', $value, $matches)) {
      return '';
    }
    if ((float)$matches[2] == 0) {
      return '0px';
    }

    return $matches[1] . $matches[2] . (empty($matches[3]) ? 'px' : $matches[3]);
  }

  /**
   * Font styles and text alignments of the legal note styling settings.
   */
  const LEGAL_NOTE_FONT_STYLES = ['normal', 'bold', 'italic', 'bold-italic'];
  const LEGAL_NOTE_ALIGNMENTS = ['left', 'center', 'right'];

  /**
   * Get a valid CSS font size, or empty string. A plain number is taken as
   * pixels.
   *
   * @param mixed $value Value entered by the merchant
   * @return string
   */
  public static function sanitizeLegalNoteFontSize($value) {
    $value = is_string($value) ? strtolower(trim($value)) : '';
    $value = str_replace(',', '.', $value);

    if (!preg_match('/^(\d+(?:\.\d+)?|\.\d+)\s*(px|em|rem|pt|%)?$/', $value, $matches)) {
      return '';
    }
    if ((float)$matches[1] <= 0) {
      return '';
    }

    return $matches[1] . (empty($matches[2]) ? 'px' : $matches[2]);
  }

  /**
   * Get a valid CSS color (hex or a color name), or empty string.
   *
   * @param mixed $value Value entered by the merchant
   * @return string
   */
  public static function sanitizeLegalNoteColor($value) {
    $value = is_string($value) ? trim($value) : '';

    return preg_match('/^(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{6}|[a-zA-Z]{3,20})$/', $value) ? $value : '';
  }

  /**
   * Get the extra CSS declarations (e.g. "padding: 8px; border: 1px solid
   * #ccc") in a form that is safe to print inside a style attribute, once it
   * is HTML-escaped. Only declarations are supported, so anything that could
   * open a new rule or a tag is removed.
   *
   * @param mixed $css Value entered by the merchant (not HTML-escaped)
   * @return string
   */
  public static function sanitizeLegalNoteExtraCss($css) {
    if (!is_string($css) || trim($css) === '') {
      return '';
    }

    $css = strip_tags($css);
    $css = preg_replace('#/\*.*?\*/#s', '', $css);
    $css = str_replace(['{', '}', '<', '>', '\\'], '', $css);
    $css = preg_replace('/expression\s*\(|javascript\s*:|@import/i', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);

    return trim($css, " ;\t\n\r");
  }

  /**
   * Build the inline style of the legal note from the styling settings, e.g.
   * "font-weight:600;color:#cc0000".
   *
   * @param array $settings Keys: font_size, font_style, color, align, extra_css
   *                        (as stored by OpenCart, i.e. HTML-escaped)
   * @return string CSS declarations, not HTML-escaped
   */
  public static function getLegalNoteStyle(array $settings) {
    $declarations = [];

    $font_size = self::sanitizeLegalNoteFontSize(isset($settings['font_size']) ? $settings['font_size'] : '');
    if ($font_size !== '') {
      $declarations[] = 'font-size:' . $font_size;
    }

    // A setting that was never saved gets the default font style.
    $font_style = isset($settings['font_style']) ? (string)$settings['font_style'] : '';
    if (!in_array($font_style, self::LEGAL_NOTE_FONT_STYLES, true)) {
      $font_style = self::LEGAL_NOTE_DEFAULT_FONT_STYLE;
    }
    $declarations[] = 'font-weight:' . (strpos($font_style, 'bold') !== false ? '600' : 'normal');
    $declarations[] = 'font-style:' . (strpos($font_style, 'italic') !== false ? 'italic' : 'normal');

    $color = self::sanitizeLegalNoteColor(isset($settings['color']) ? $settings['color'] : '');
    if ($color !== '') {
      $declarations[] = 'color:' . $color;
    }

    // A setting that was never saved (null) gets the default alignment; a
    // saved empty value means the alignment of the theme.
    $align = isset($settings['align']) ? (string)$settings['align'] : self::LEGAL_NOTE_DEFAULT_ALIGN;
    if (in_array($align, self::LEGAL_NOTE_ALIGNMENTS, true)) {
      $declarations[] = 'text-align:' . $align;
    }

    // An empty or invalid margin means the default margin.
    foreach (self::LEGAL_NOTE_DEFAULT_MARGINS as $side => $default) {
      $margin = self::sanitizeLegalNoteMargin(isset($settings['margin_' . $side]) ? $settings['margin_' . $side] : '');
      $declarations[] = 'margin-' . $side . ':' . ($margin !== '' ? $margin : $default);
    }

    // The extra CSS comes last, so it can override the controls above.
    // Form input is stored HTML-escaped by OpenCart.
    $extra_css = self::sanitizeLegalNoteExtraCss(
      html_entity_decode(isset($settings['extra_css']) ? (string)$settings['extra_css'] : '', ENT_QUOTES, 'UTF-8')
    );
    if ($extra_css !== '') {
      $declarations[] = $extra_css;
    }

    return implode(';', $declarations);
  }

}
