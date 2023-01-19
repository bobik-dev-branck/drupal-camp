<?php

namespace Drupal\exchange_rates;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Provides functionality for fetching exchange rates via API.
 */
class ExchangeRatesService {

  use StringTranslationTrait;

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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {
    $this->client = $client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
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
   * Builds URL with all get parameters.
   *
   * @param string $url
   *   The static part of URL for API.
   *
   * @return string
   *   The full URL for API.
   */
  public function buildUrl($url) {
    $defaultDate = $this->getConfig('date');
    $tail = '&sort=exchangedate&order=desc&json';

    $fullUrl = $url . '?start=' . $defaultDate . '&end=' . date('Ymd') . $tail;

    return $fullUrl;
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

        $data[$exchangeRate->cc][$exchangeRate->exchangedate] = $exchangeRate->rate;
      }
    }
    catch (\Exception $e) {
      $this->sendLog($e);
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
      $this->sendLog($e);
      return FALSE;
    }

    return $result;
  }

  /**
   * Sends logs message.
   *
   * @param object $error
   *   The Exceptions.
   */
  public function sendLog($error) {
    $message = $this->t('API isn\'t available - @error', [
      '@error' => $error->getMessage(),
    ]);

    $this->logger->get('exchange_rates')->notice($message);
  }

}
