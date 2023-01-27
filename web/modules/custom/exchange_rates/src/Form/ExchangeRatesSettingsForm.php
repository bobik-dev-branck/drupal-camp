<?php

namespace Drupal\exchange_rates\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
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
    $date = new DrupalDateTime('', 'UTC');

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
      '#ajax' => [
        'callback' => '::urlAjaxCheck',
        'wrapper' => 'checkbox-container',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set start range get data'),
      '#default_value' => $config->get('date') ?? $date->format('Ymd'),
      '#description' => $this->t('WARNING! Write date in format - 20230101'),
      '#maxlength' => 8,
    ];

    $form['currency'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'checkbox-container',
      ],
      '#tree' => TRUE,
    ];

    // If used AJAX builds checkboxes with API data.
    // Else they build with config data.
    $isTriger = $form_state->getTriggeringElement();

    if ($isTriger) {
      $url = $this->exchangeRates->buildUrl($form_state->getValue('url'));
      $data = $this->exchangeRates->getExchangeRates($url);

      if ($data) {
        foreach ($data as $currency) {
          $form['currency'][$currency['currency']] = [
            '#type' => 'checkbox',
            '#title' => $currency['currency'],
            '#default_value' => FALSE,
          ];

          $messenge = $currency['date'] . ' - ' . $currency['currency'] . ' - ' . $currency['rate'];
          $this->messenger()->addStatus($messenge);

        }

      }

    }
    else {
      $enabledCurrency = $config->get('currency');

      if ($enabledCurrency) {
        foreach ($enabledCurrency as $currency => $status) {
          $form['currency'][$currency] = [
            '#type' => 'checkbox',
            '#title' => $currency,
            '#default_value' => $status,
          ];

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
      $date = new DrupalDateTime('', 'UTC');

      if (!is_numeric($form_state->getValue('date'))) {
        $form_state->setErrorByName('date', $this->t('Needs to enter only numeric'));

      }

      if (strlen($form_state->getValue('date')) != 8) {
        $form_state->setErrorByName('date', $this->t('Too little numeric'));

      }

      if ($form_state->getValue('date') > $date->format('Ymd')) {
        $form_state->setErrorByName('date', $this->t('The date is not valid'));

      }

    }

    if ($form_state->getValue('url')) {
      $checkLink = $this->exchangeRates
        ->checkRequest($this->exchangeRates->buildUrl($form_state->getValue('url')));

      if (!$checkLink) {
        $form_state->setErrorByName('url', $this->t('Wrong link or API do not work'));

      }

    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gets default settings for currency.
    $isShow = $this->exchangeRates->getConfig('currency');

    $this->config('exchange_rates.settings')
      ->set('show_block', $form_state->getValue('show_block'))
      ->set('url', $form_state->getValue('url'))
      ->set('date', $form_state->getValue('date'))
      ->set('currency', $form_state->getValue('currency'))
      ->save();
    parent::submitForm($form, $form_state);

    // Compares currency settings and send user messages.
    $withForm = $form_state->getValue('currency');
    if (!empty($withForm) && $isShow) {
      foreach ($withForm as $currency => $show) {
        if ($show != $isShow[$currency]) {

          // Sends messages for Settings form and writes logs.
          if ($show) {
            $this->messenger->addWarning($this->t('The @currency has been enabled', [
              '@currency' => $currency,
            ]));

            $this->logger('exchange_rates')
              ->info($this->t('The @currency has been enabled', [
                '@currency' => $currency,
              ]));
          }
          else {
            $this->messenger->addWarning($this->t('The @currency has been disabled', [
              '@currency' => $currency,
            ]));

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

  /**
   * AJAX callback function for 'url' form field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form part will need to be rebuilt.
   */
  public function urlAjaxCheck(array &$form, FormStateInterface $form_state) {
    $url = $this->exchangeRates->buildUrl($form_state->getValue('url'));
    $checkLink = $this->exchangeRates->checkRequest($url);

    if (!$checkLink) {
      $message = 'Wrong link or API do not work';
      $this->messenger()->addError($message);
      return $form['currency'];

    }

    return $form['currency'];

  }

}
