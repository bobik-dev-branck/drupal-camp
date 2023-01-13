<?php

namespace Drupal\exchange_rates\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\exchange_rates\ExchangeRatesService;
use Psr\Container\ContainerInterface;

/**
 * Provides data about currency exchange rates.
 *
 * @Block(
 *   id = "exchange_rates",
 *   admin_label = @Translation("Exchange_rates"),
 * )
 */
class ExchangeRatesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\exchange_rates\ExchangeRatesService $exchange_rates
   *   The Exchange Rates Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExchangeRatesService $exchange_rates) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exchangeRates = $exchange_rates;
  }

  /**
   * Create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('exchange_rates.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $showBlock = $this->exchangeRates->getConfig('show_block');
    if ($showBlock == FALSE) {
      return;
    }

    if ($showBlock == TRUE) {
      $url = $this->exchangeRates->getConfig('url');

      if ($url) {
        $data = $this->exchangeRates->getExchangeRates($url);

        if (!empty($data)) {
          $renderable = [
            '#theme' => 'block_exchange_rates',
            '#data' => $data,
          ];

          return $renderable;
        }

      }

    }

  }

}
