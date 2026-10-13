<?php

/**
 * Class to provide API end points.
 */
class ViaBillServices {
  const ADDON_NAME = ViaBillConstants::AFFILIATE;
  const API_PROTOCOL = ViaBillConstants::PROTOCOL;
  const BASE_URL = 'https://secure.viabill.com';

  private static $registry = null;  

  // These endpoints contain references to the addon name.
  const API_END_POINTS = [
    'myviabill'           => [
      'endpoint'        => '/api/addon/ADDON_NAME/myviabill',
      'method'          => 'GET',
      'required_fields' => ['key', 'signature'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        400 => 'messages.viabillApiMessages.requestError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{key}#{secret}',
    ],
    'notifications'       => [
      'endpoint'        => '/api/addon/ADDON_NAME/notifications',
      'method'          => 'GET',
      'required_fields' => ['key', 'signature'],
      'optional_fields' => ['platform', 'platform_ver', 'module_ver'],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        400 => 'messages.viabillApiMessages.requestError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{key}#{secret}',
    ],
    'checkout'            => [
      'endpoint'        => '/api/checkout-authorize/addon/ADDON_NAME',
      'method'          => 'POST',
      'required_fields' => [
        'protocol',
        'apikey',
        'transaction',
        'order_number',
        'amount',
        'currency',
        'success_url',
        'cancel_url',
        'callback_url',
        'test',
        'sha256check',
        'tbyb'
      ],
      'optional_fields' => ['customParams', 'cartParams'],
      'sha256check'        => '{apikey}#{amount}#{currency}#{transaction}#{order_number}#{success_url}#{cancel_url}#{secret}',
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        301 => 'messages.viabillApiMessages.permanentRedirect',
        302 => 'messages.viabillApiMessages.temporaryRedirect',
        400 => 'messages.viabillApiMessages.requestError',
        403 => 'messages.viabillApiMessages.debtorCreditError',
        409 => 'messages.viabillApiMessages.requestFrequencyError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
    ],
    'capture_transaction' => [
      'endpoint'        => '/api/transaction/capture',
      'method'          => 'POST',
      'required_fields' => ['id', 'apikey', 'signature', 'amount', 'currency'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        400 => 'messages.viabillApiMessages.requestError',
        403 => 'messages.viabillApiMessages.debtorCreditError',
        409 => 'messages.viabillApiMessages.requestFrequencyError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{id}#{apikey}#{amount}#{currency}#{secret}',
    ],
    'cancel_transaction'  => [
      'endpoint'        => '/api/transaction/cancel',
      'method'          => 'POST',
      'required_fields' => ['id', 'apikey', 'signature'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        400 => 'messages.viabillApiMessages.requestError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{id}#{apikey}#{secret}',
    ],
    'refund_transaction'  => [
      'endpoint'        => '/api/transaction/refund',
      'method'          => 'POST',
      'required_fields' => ['id', 'apikey', 'signature', 'amount', 'currency'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        400 => 'messages.viabillApiMessages.requestError',
        403 => 'messages.viabillApiMessages.spxAccountInactive',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{id}#{apikey}#{amount}#{currency}#{secret}',
    ],
    'renew_transaction'   => [
      'endpoint'        => '/api/transaction/renew',
      'method'          => 'POST',
      'required_fields' => ['id', 'apikey', 'signature'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        400 => 'messages.viabillApiMessages.requestError',
        403 => 'messages.viabillApiMessages.debtorCreditError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{id}#{apikey}#{secret}',
    ],
    'transaction_status'  => [
      'endpoint'        => '/api/transaction/status',
      'method'          => 'GET',
      'required_fields' => ['id', 'apikey', 'signature'],
      'optional_fields' => [],
      'status_codes'    => [
        200 => 'messages.viabillApiMessages.successfulRequest',
        204 => 'messages.viabillApiMessages.noContentResponse',
        400 => 'messages.viabillApiMessages.requestError',
        500 => 'messages.viabillApiMessages.apiServerError',
      ],
      'signature'       => '{id}#{apikey}#{secret}',
    ],
  ];  

  public static function setRegistry($registry)
  {
    self::$registry = $registry;
  }

  public static function getRegistry()
  {
    return self::$registry;
  }  

  /**
   * Get the API end point.
   */
  public static function getEndPointData(string $endPoint = '', array $data = []) {
    $ed = self::getApiEndPoint($endPoint);
    if (!empty($ed)) {
      $endPoint = $ed['endpoint'];
      $method = $ed['method'];
      $requestData = [];

      $testMode = empty($data['test'])?self::getTestMode():$data['test'];

      foreach ($ed['required_fields'] as $field) {
        $isTest = ($field === 'test');
        // Check for signature/sha256check fields.
        if (array_key_exists($field, $ed)) {
          $format = $ed[$field];
          // Parse the format to generate a signature if one is required.
          try {
            $format = self::parseFormat($format, $data);
          }
          catch (\Exception $e) {
            $error_msg = 'Error parsing format: ' . $e->getMessage();
            ViaBillHelper::log($error_msg, 'error');
            return FALSE;
          }
          // For test checkouts ViaBill expects the test flag after the
          // secret in the signed string (callbacks never carry it).
          if ($field === 'sha256check' && filter_var($testMode, FILTER_VALIDATE_BOOLEAN)) {
            $format .= '#true';
          }
          $requestData[$field] = self::hashSignature($format);
          // Process the remaining required fields.
        }
        elseif (array_key_exists($field, $data)) {
          // Make sure the test field is set to true
          // if test mode is enabled globally
          // or for this specific request.
          if ($isTest) {
            $requestData[$field] = $data[$field];
          }
          elseif ($field === 'country') {
            $requestData[$field] = (self::validIso($data[$field]) ? strtoupper(trim($data[$field])) : $data[$field]);
          }
          else {
            $requestData[$field] = $data[$field];
          }

        }
        elseif ($field === 'protocol') {
          $requestData[$field] = self::API_PROTOCOL;
        }
        elseif ($isTest) {
          $requestData[$field] = $testMode;
        }
        else {
          $error_msg = 'Data is missing required field: ' . $field;
          ViaBillHelper::log($error_msg, 'error');
          return FALSE;
        }
      }

      foreach ($ed['optional_fields'] as $field) {
        if (array_key_exists($field, $data)) {
          $requestData[$field] = $data[$field];
        }
      }
      $requestData = self::prepareData($requestData);                  

      return [
        'endpoint' => $endPoint,
        'method' => $method,
        'data' => $requestData,
      ];
    }
    return FALSE;
  }

  /**
   * Build and send a request to a ViaBill API end point.
   *
   * The request body is built by getEndPointData() from the end point's
   * declared fields only, and its signature is calculated there. The API
   * secret is used for the signature but is never sent to ViaBill.
   *
   * @param string $endPoint The end point name, e.g. 'capture_transaction'
   * @param array $data The request values (may include 'secret' for signing)
   * @return array The sendApiRequest() result, or an error result
   */
  public static function request(string $endPoint, array $data): array {
    $endPointData = self::getEndPointData($endPoint, $data);

    if (empty($endPointData)) {
      return [
        'status' => 'error',
        'message' => 'Could not build the ' . $endPoint . ' request.',
      ];
    }

    return ViaBillHelper::sendApiRequest($endPointData['endpoint'], $endPointData['data'], $endPointData['method']);
  }

  /**
   * Hash a signature string with the algorithm of the configured protocol
   * (MD5 for protocol 3.0, SHA-256 otherwise).
   *
   * @param string $value
   * @return string
   */
  public static function hashSignature(string $value): string {
    return ViaBillConstants::PROTOCOL == '3.0' ? md5($value) : hash('sha256', $value);
  }

  public static function getApiEndPoint($end_point) {
    // Check if the default ADDON name is still used.
    $addon_name = self::ADDON_NAME;

    if (isset(self::API_END_POINTS[$end_point])) {
      $end_point_settings = self::API_END_POINTS[$end_point];
      $end_point_settings['endpoint'] = str_replace(
            'ADDON_NAME',
            $addon_name,
            $end_point_settings['endpoint']
        );

      // Ensure proper formatting of URLs
      $baseUrl = self::BASE_URL;      
      $endPoint = ltrim($end_point_settings['endpoint'], '/');
      $end_point_settings['endpoint'] = $baseUrl . '/' . $endPoint;

      return $end_point_settings;
    }
    // Unknown end point: log it and let the caller handle the failure,
    // instead of terminating the request.
    ViaBillHelper::log('Unknown API End Point: ' . $end_point, 'error');

    return FALSE;
  }

    /**
   * Utility function to parse the signature format of a ViaBill request.
   *
   * @param string $format
   *   The format string.
   * @param array $data
   *   The data array.
   *
   * @return mixed
   *   Returns a text with the processed format string.
   *
   * @throws Exception
   */
  protected static function parseFormat($format, &$data) {

    $apiKey = empty($data['apikey'])?self::getApiKeyMode():$data['apikey'];
    $apiSecret = empty($data['secret'])?self::getApiSecretMode():$data['secret'];
    $testMode = empty($data['test'])?self::getTestMode():$data['test'];

    preg_match_all('/(?:\{([^\{\}#]+)\}#?)/', $format, $formatFields);
    if (empty($formatFields)) {
      throw new \Exception(__METHOD__ . ': Invalid format string - Format does not contain any fields.');
    }
    foreach ($formatFields[1] as $key) {
      if (array_key_exists($key, $data)) {
        $val = $data[$key];
        if ($key === 'country') {
          $val = (self::validIso($val) ? strtoupper(trim($val)) : $val);
        }
        $format = str_replace('{' . $key . '}', $val, $format);
      }
      elseif ($key === 'secret') {
        if ($apiSecret === NULL) {
          throw new \Exception('You must set the apiSecret with ' . __CLASS__ . '::apiSecret() before calling ' . __METHOD__ . '().');
        }
        $format = str_replace('{' . $key . '}', $apiSecret, $format);
      }
      elseif (in_array($key, ['key', 'apikey', 'apiKey'])) {
        if ($apiKey === NULL) {
          throw new \Exception('You must set the apiKey with ' . __CLASS__ . '::apiSecret() before calling ' . __METHOD__ . '().');
        }
        $format = str_replace('{' . $key . '}', $apiKey, $format);

      }
      elseif ($key === 'protocol') {
        $format = str_replace('{' . $key . '}', self::API_PROTOCOL, $format);
      }
      elseif ($key === 'test') {
        $format = str_replace('{' . $key . '}', $testMode, $format);
      }
      else {
        throw new \Exception('Data is missing a required signature field; ' . $key);
      }
    }
    return trim($format);
  }

  /**
   * Utility function to prepare the request data.
   *
   * @param mixed &$input
   *   An array or string containing boolean values.
   *
   * @return mixed
   *   Works in-place, but can return the converted input to a new variable
   */
  protected static function prepareData(&$input) {
    $checkVal = static function ($value) {
      if (is_bool($value)) {
        $value = ($value ? 'true' : 'false');
      }
      return $value;
    };
    if (is_array($input)) {
      foreach ($input as $key => $value) {
        if (is_array($value)) {
          $input[$key] = self::prepareData($value);
        }
        else {
          $input[$key] = $checkVal($value);
        }
      }
    }
    else {
      $input = $checkVal($input);
    }
    return $input;
  }

  /**
   * Utility function to verify the country's ISO code.
   *
   * @param string $country
   *   A two character country code to check against the ISO codes array.
   * @param bool $silent
   *   If true, returns false if country code is not a valid ISO 3166-1
   *   alpha 2 code, instead of throwing an exception.
   *
   * @return bool
   *   Returns true if the specified country code is a valid ISO 3166-1
   *   alpha 2 code, or false if not.
   *
   * @throws Exception
   *   When specified value is not a valid ISO 3166-1 alpha 2 country code
   *   and $silent=false.
   */
  public static function validIso($country = '', $silent = TRUE): bool {
    $country = strtoupper(trim($country));
    // Return false if country code is too long, or too short.
    if (strlen($country) !== 2) {
      return FALSE;
    }

    if (in_array($country, ViaBillConstants::ISO_CODES, FALSE)) {
      return TRUE;
    }
    if ($silent) {
      return FALSE;
    }
    $message = sprintf('%s: Value %s is not a valid ISO 3166-1 alpha 2 Country Code.', __METHOD__, $country);
    throw new \Exception($message);
  }

  public static function getApiKeyMode()
  {
    if (self::$registry) {
      $config = self::$registry->get('config');
      return $config->get('payment_viabill_api_key');
    }
    return '';
  }

  public static function getApiSecretMode()
  {
    if (self::$registry) {
      $config = self::$registry->get('config');
      return $config->get('payment_viabill_secret_key');
    }
    return '';
  }

  public static function getTestMode()
  {
    if (self::$registry) {
      $config = self::$registry->get('config');
      return $config->get('payment_viabill_test');
    }
    return '';
  }
  

}
