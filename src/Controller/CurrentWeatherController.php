<?php

namespace Drupal\current_weather\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for /weather page.
 */
class CurrentWeatherController extends ControllerBase {

  /**
   * Returns /weather success page with results or error message.
   *
   * Params are based on user link input arguments (/weather/[city_name]/[country_code]) defined in current_weather.routing.yml.
   *
   * @param string $city_name
   *   City name.
   * @param string $country_code
   *   Two letter country code.
   *
   * @return array
   *   Simple render array.
   */
  public function currentWeatherShowResultsPage($city_name, $country_code) {

    // Create weather service.
    $weather_service = \Drupal::service('current_weather.openweathermap');

    // Check if page arguments aren't empty (/weather/[city_name]/[country_code]) and set query data accordingly.
    if (!empty($city_name)) {
      $weather_service->setQueryData($city_name, $country_code);
    }

    // Get weather service results.
    $result = $weather_service->getCurrentWeatherData();

    // We're setting up cache tags so that this weather page gets new data if module config page gets changed.
    // Also we're attaching CSS library to be able to theme results template.
    return [
      '#theme' => 'current_weather_results',
      '#current_weather_results' => $result,
      '#cache' => [
        'tags' => ['current_weather'],
      ],
      '#attached' => [
        'library' => [
          'current_weather/weather-widget',
        ],
      ],
    ];
  }

}
