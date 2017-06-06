<?php
if (!defined('ABSPATH'))
  exit;

class Skoorin_API {
  public static $url = 'https://dgmtrx.com/api.php';
  public static function get($params, $content_type = 'json') {
    if (!is_array($params))
      return null;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => self::$url.'?'.http_build_query($params),
      CURLOPT_RETURNTRANSFER => true
    ));

    $response = curl_exec($curl);
    $output = $response
      ? ($content_type === 'json' ? json_decode($response) : $response)
      : array(
          'error' => curl_error($curl),
          'code' => curl_errno($curl)
        );
    
    curl_close($curl);

    return $output;
  }
}