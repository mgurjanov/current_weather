<?php

namespace Drupal\current_weather\Services;

use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;

/**
 * Class that defines openweathermap.org service.
 */
class OpenWeatherMapService {

  /**
   * Holds query parameters that are used to create HTTP request.
   *
   * @var array
   */
  private $queryData = [
    'city_name' => '',
    'country_code' => '',
    'api_endpoint' => '',
    'api_key' => '',
  ];

  /**
   * Holds results (fetched weather data).
   *
   * @var array
   */
  private $result = [
    'city_name' => '',
    'country_code' => '',
    'current_temp' => '',
    'icon' => '',
    'flag' => '',
    'status' => NULL,
    'message' => '',
  ];

  /**
   * Default constructor.
   */
  public function __construct() {
    // Get config settings.
    $config = \Drupal::config('current_weather.settings');
    // Set query data from config which can be overriden by page arguments at /weather page (e.g. /weather/[city_name]/[country_code])
    $this->setQueryData($config->get('city_name'), $config->get('country_code'));
    // Set API endpoint from config.
    $this->queryData['api_endpoint'] = $config->get('api_endpoint');
    // Set API key from config.
    $this->queryData['api_key'] = $config->get('api_key');
  }

  /**
   * Sets query data.
   *
   * @param string $city_name
   *   City name.
   * @param string $country_code
   *   Two letter country code.
   */
  public function setQueryData($city_name, $country_code) {
    $this->queryData['city_name'] = $city_name;
    $this->queryData['country_code'] = $country_code;
  }

  /**
   * Returns city name.
   *
   * @return mixed
   *   Returns private attribute that holds city name
   */
  public function getCityName() {
    return $this->queryData['city_name'];
  }

  /**
   * Returns country code.
   *
   * @return mixed
   *   Returns private attribute that holds country code.
   */
  public function getCountryCode() {
    return $this->queryData['country_code'];
  }

  /**
   * API endpoint existence validation.
   *
   * @return bool
   *   Returns TRUE if API endpoint is entered in config form, or FALSE if not.
   */
  public function existsApiEndpoint() {
    return !empty($this->queryData['api_endpoint']) ? TRUE : FALSE;
  }

  /**
   * API key existence validation.
   *
   * @return bool
   *   Returns TRUE if API key is entered in config form, or FALSE if not.
   */
  public function existsApiKey() {
    return !empty($this->queryData['api_key']) ? TRUE : FALSE;
  }

  /**
   * Helper function that retrieves country flag image from https://restcountries.eu/ service.
   *
   * @param string $country_code
   *   Two letter country code.
   *
   * @return string
   *   String holding country image link.
   */
  private function getCountryFlag($country_code) {
    if (!empty($country_code)) {

      // Create http client.
      $client = \Drupal::httpClient();

      try {
        // Set query parameters by instructions given at https://restcountries.eu/
        $query = [
          'codes' => $country_code,
        ];

        // Create URL.
        $url = Url::fromUri('https://restcountries.eu/rest/v2/alpha');
        // Pass query options.
        $url->setOption('query', $query);

        // Make request to url.
        $request = $client->get($url->toString());
        // Get result body and decode JSON.
        $response = json_decode($request->getBody(), TRUE);

        // Return icon image link.
        return $response['0']['flag'];

      }
      catch (RequestException $e) {
        // HTTP client error happened. Log this for admin to be able to see the exact message.
        \Drupal::logger('current_weather')->error('HTTP Client error');
        \Drupal::logger('current_weather')->error($e->getMessage());

        return '';
      }
    }
    else {
      return '';
    }
  }

  /**
   * Fetches current weather data from openweathermap.org.
   *
   * @return array
   *   Array with data fetched from weather service.
   */
  public function getCurrentWeatherData() {

    // Check if API key / API endpoint are entered in config page.
    if ($this->existsApiKey() && $this->existsApiEndpoint()) {
      // City name is the most important data so check if not empty.
      if (!empty($this->getCityName())) {

        // Create http client.
        $client = \Drupal::httpClient();

        try {
          // Set query parameters by instructions given at https://openweathermap.org/current
          // Units parameter is hardcoded but could be provided at config page as a list of available options. Not required by the task.
          $query = [
            'q' => $this->getCityName() . ',' . $this->getCountryCode(),
            'appid' => $this->queryData['api_key'],
            'units' => 'metric',
          ];

          // Create URL.
          $url = Url::fromUri($this->queryData['api_endpoint']);
          // Pass query options.
          $url->setOption('query', $query);

          // Make request to url.
          $request = $client->get($url->toString());
          // Get result body and decode JSON.
          $response = json_decode($request->getBody(), TRUE);

          // Set result parameters based on response JSON data.
          $this->result['city_name'] = $response['name'];
          $this->result['country_code'] = $response['sys']['country'];
          $this->result['current_temp'] = floor($response['main']['temp']);
          $this->result['icon'] = $response['weather']['0']['icon'];
          // Get the country flag image from external service based on country code from weather data.
          $this->result['flag'] = $this->getCountryFlag($response['sys']['country']);
          // Set status to TRUE as we received data from the service.
          $this->result['status'] = TRUE;
          $this->result['message'] = 'Got valid weather results.';

          return ['result' => $this->result];

        }
        catch (RequestException $e) {
          // HTTP client error happened. Log this for admin to be able to see the exact message.
          // We don't want to expose exact message with API key to the end user.
          \Drupal::logger('current_weather')->error('HTTP Client error');
          \Drupal::logger('current_weather')->error($e->getMessage());

          // Set result status to FALSE as HTTP client returned an error.
          $this->result['status'] = FALSE;
          // Generate a message for the end user.
          $this->result['message'] = 'No results found for given search parameters, service error or API key invalid.';

          return ['result' => $this->result];
        }
      }
      else {
        // No city name and/or country code given, so set result to FALSE.
        $this->result['status'] = FALSE;
        $this->result['message'] = 'Invalid city name and/or country code!';

        return ['result' => $this->result];
      }

    }
    else {
      // No valid API key is provided.
      $this->result['status'] = FALSE;
      $this->result['message'] = 'No valid API key and/or API endpoint supplied!';

      return ['result' => $this->result];
    }

  }

}
