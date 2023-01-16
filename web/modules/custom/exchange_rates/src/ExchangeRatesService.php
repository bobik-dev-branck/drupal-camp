<?php

namespace Drupal\exchange_rates;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides functionality for fetching exchange rates via API.
 */
class ExchangeRatesService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an ExchangeRatesService object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the value of the config name of the configuration object to construct.
   *
   * @param string $configName
   *   The config name.
   *
   * @return string|bool|null
   *   The default settings for block.
   */
  public function getConfig($configName) {
    $config = $this->configFactory->get('exchange_rates.settings');

    return $config->get($configName);
  }

  /**
   * Gets Exchange Rates with API.
   *
   * @param string $url
   *   The url to API Exchange Rates.
   *
   * @return array|null
   *   The uri link, NULL otherwise.
   */
  public function getExchangeRates($url) {
    try {
      $request = $this->client->get($url)->getBody();
      $exchangeRates = json_decode($request);
      foreach ($exchangeRates as $exchangeRate) {
        $data[$exchangeRate->cc] = $exchangeRate->rate;
      }
    }
    catch (RequestException $e) {
      return;
    }

    return $data;
  }

  /**
   * Checks API status code.
   *
   * @param string $url
   *   The API URL.
   *
   * @return bool
   *   Return API Status code.
   */
  public function checkRequest($url) {
    try {
      $check = $this->client->get($url)->getStatusCode();
      $check == 200 ? $result = TRUE : $result = FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $result;
  }

}
