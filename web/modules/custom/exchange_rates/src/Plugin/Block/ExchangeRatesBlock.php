<?php

namespace Drupal\exchange_rates\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides data about currency exchange rates.
 *
 * @Block(
 *   id = "exchange_rates",
 *   admin_label = @Translation("Exchange_rates"),
 * )
 */
class ExchangeRatesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $httpClient = \Drupal::httpClient();
    $date = date("Ymd");
    $response = $httpClient
      ->get('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?date=' . $date . '&json')
      ->getBody();
    $exchangeRates = json_decode($response);

    foreach ($exchangeRates as $exchangeRate) {
      $data[$exchangeRate->cc] = $exchangeRate->rate;
    }

    $renderable = [
      '#theme' => 'block_exchange_rates',
      '#data' => $data,
    ];

    return $renderable;

  }

}
