<?php

namespace Drupal\current_weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Administration form for Current Weather module.
 */
class CurrentWeatherConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'current_weather.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'current_weather_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('current_weather.settings');

    $form['city_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City name'),
      '#description' => $this->t('Please enter default city name.'),
      '#default_value' => $config->get('city_name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['country_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country code'),
      '#description' => $this->t('Please enter default country code.'),
      '#default_value' => $config->get('country_code'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Openweathermap.org API endpoint'),
      '#description' => $this->t('Please enter openweathermap.org API endpoint link.'),
      '#default_value' => $config->get('api_endpoint'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Openweathermap.org API Key'),
      '#description' => $this->t('Please enter openweathermap.org API key.'),
      '#default_value' => $config->get('api_key'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the current settings.
    $config_factory = \Drupal::service('config.factory');
    /**
     * @var \Drupal\Core\Config\Config $config
     */
    $config = $config_factory->getEditable('current_weather.settings');

    // Set new values.
    $config->set('city_name', $form_state->getValue('city_name'))->save();
    $config->set('country_code', $form_state->getValue('country_code'))->save();
    $config->set('api_endpoint', $form_state->getValue('api_endpoint'))->save();
    $config->set('api_key', $form_state->getValue('api_key'))->save();

    // Invalidate cache tags so that controller page shows correct values.
    Cache::invalidateTags(['current_weather']);

    parent::submitForm($form, $form_state);
  }

}
