(
  function ($, Drupal, drupalSettings) {
    'use strict'

    Drupal.behaviors.moneyExchange = {
      attach: function (context, settings) {
        if (context !== document) {
          return;
        }

        const ctx = context.getElementById('exchangeRates');
        const obj = drupalSettings.exchange_rates;
        const date = drupalSettings.currency_data.date;

        new Chart(ctx, {
          type: 'line',
          data: {
            labels: date,
            datasets: obj,
          },
          options: {
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      }
    };
  }
)(jQuery, Drupal, drupalSettings);
