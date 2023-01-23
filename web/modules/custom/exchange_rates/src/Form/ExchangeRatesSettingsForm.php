<?php

namespace Drupal\exchange_rates\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\exchange_rates\ExchangeRatesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Exchange rates settings for this site.
 */
class ExchangeRatesSettingsForm extends ConfigFormBase {

  /**
   * The Constructor for Exchange Rates Settings Form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\exchange_rates\ExchangeRatesService $exchange_rates
   *   The Exchange Rates Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExchangeRatesService $exchange_rates) {
    parent::__construct($config_factory);
    $this->exchangeRates = $exchange_rates;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('exchange_rates.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchange_rates_exchange_rates';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['exchange_rates.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('exchange_rates.settings');

    $form['show_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do you want to show block?'),
      '#default_value' => $config->get('show_block') ?? FALSE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rates API'),
      '#description' => $this
        ->t('WARNING! Use only JSON API and without get parameters'),
      '#default_value' => $config->get('url') ?? '',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $form['date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set start range get data'),
      '#default_value' => $config->get('date') ?? date('Ymd'),
      '#description' => $this->t('WARNING! Write date in format - 20230101'),
      '#maxlength' => 8,
    ];

    // If API set and return data will show this fieldset.
    if ($config->get('url')) {
      $url = $this->exchangeRates->buildUrl($config->get('url'));
      $checkUrl = $this->exchangeRates->checkRequest($url);

      if ($checkUrl) {
        $form['currency'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Choose currency will show'),
          '#tree' => TRUE,
        ];

        $enabledCurrency = $config->get('currency');

        if ($enabledCurrency) {
          foreach ($enabledCurrency as $currency => $status) {
            $form['currency'][$currency] = [
              '#type' => 'checkbox',
              '#title' => $currency,
              '#default_value' => $status ?? FALSE,
            ];

          }

        } else {
          $data = $this->exchangeRates->getExchangeRates($url);

          foreach ($data as $currency ) {
            $form['currency'][$currency['currency']] = [
              '#type' => 'checkbox',
              '#title' => $currency['currency'],
              '#default_value' => FALSE,
            ];

          }

        }

      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('date')) {
      if (!is_numeric($form_state->getValue('date'))) {
        $form_state->setErrorByName('date', $this->t('Needs to enter only numeric'));

      }

      if (strlen($form_state->getValue('date')) != 8) {
        $form_state->setErrorByName('date', $this->t('Too little numeric'));

      }

      if ($form_state->getValue('date') > date('Ymd')) {
        $form_state->setErrorByName('date', $this->t('The date isn\'t valid'));

      }

    }

    if ($form_state->getValue('url')) {
      $checkLink = $this->exchangeRates
        ->checkRequest($this->exchangeRates->buildUrl($form_state->getValue('url')));

      if (!$checkLink) {
        $form_state->setErrorByName('url', $this->t('Wrong link or API don\'t work'));

      }

    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gets default settings for currency.
    $defaultSettings = $this->exchangeRates->getConfig('currency');
    if ($defaultSettings) {
      foreach ($defaultSettings as $currency => $shows) {
        $isShow[$currency] = $shows;
      }
    }

    $this->config('exchange_rates.settings')
      ->set('show_block', $form_state->getValue('show_block'))
      ->set('url', $form_state->getValue('url'))
      ->set('date', $form_state->getValue('date'))
      ->set('currency', $form_state->getValue('currency'))
      ->save();
    parent::submitForm($form, $form_state);

    // Compares currency settings and send user messages.
    $withForm = $form_state->getValue('currency');
    if (!empty($withForm) && isset($isShow)) {
      foreach ($withForm as $currency => $show) {
        if ($show != $isShow[$currency]) {

          // Message for Settings form.
          if ($show) {
            $this->messenger->addWarning($this->t('The @currency has been enabled', [
              '@currency' => $currency,
            ]));
          }
          else {
            $this->messenger->addWarning($this->t('The @currency has been disabled', [
              '@currency' => $currency,
            ]));
          }

          // Message for Logs.
          if ($show) {
            $this->logger('exchange_rates')
              ->info($this->t('The @currency has been enabled', [
                '@currency' => $currency,
              ]));
          }
          else {
            $this->logger('exchange_rates')
              ->info($this->t('The @currency has been disabled', [
                '@currency' => $currency,
              ]));
          }

        }

      }

    }

    // Saving Exchange Rates to the Database if the block is enabled.
    if ($form_state->getValue('show_block')) {
      $this->exchangeRates->runSaveDataWithForm($form_state->getValue('date'));

    }

  }

}
