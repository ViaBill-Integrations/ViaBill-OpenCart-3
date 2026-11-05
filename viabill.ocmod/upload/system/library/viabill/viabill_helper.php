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
    static $texts = [];

    $language_code = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$language_code));

    if (!isset($texts[$language_code])) {
      $texts[$language_code] = self::LEGAL_NOTE_DEFAULT_TEXT;
      // DIR_CATALOG is only defined in the admin.
      $catalog_dir = defined('DIR_CATALOG') ? DIR_CATALOG : DIR_APPLICATION;
      $file = $catalog_dir . 'language/' . $language_code . '/extension/payment/viabill.php';

      if ($language_code !== '' && is_file($file)) {
        // Language files fill a local $_ array.
        $_ = [];
        require $file;
        if (!empty($_['text_legal_note_default'])) {
          $texts[$language_code] = (string)$_['text_legal_note_default'];
        }
      }
    }

    return $texts[$language_code];
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

}
