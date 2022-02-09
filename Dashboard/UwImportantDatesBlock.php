<?php

namespace Drupal\uw_dashboard\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;

/**
 * UW events block.
 *
 * @Block(
 *   id = "uw_news_block_important_dates",
 *   admin_label = @Translation("UW Important Dates"),
 * )
 */
class UwImportantDatesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * Constructs a BlockComponentRenderArray object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   An HTTP client.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // The URL for api events.
    $url = 'https://openapi.data.uwaterloo.ca/v3/ImportantDates';

    // Make the call to the API.
    $important_dates = $this->httpClient->get($url, [
      'headers' => [
        'X-API-KEY' => settings::get('open_data_api_key'),
      ],
    ]);

    // Decode the API call.
    $important_dates = json_decode($important_dates->getBody());

    // Setup important dates based on max items from form.
    $important_dates = array_slice($important_dates, count($important_dates) - $this->configuration['max_items'], $this->configuration['max_items']);

    // Step through each important date and setup for template.
    for ($i = $this->configuration['max_items'] - 1; $i >= 0; $i--) {

      // Reset the term dates, so that we only get the dates
      // for the important date that we are on.
      $term_dates = [];

      // Strip all the tags from the description.
      $content = strip_tags(
        $important_dates[$i]->description,
        ['<br>', '<p>']
      );

      // Truncate the content based on config from form.
      $content = Unicode::truncate(
        $content,
        $this->configuration['max_chars'],
        NULL,
        TRUE
      );

      // Fix any faulty tags on the content.
      $content = Html::normalize($content);

      // Step through each date and setup array for template.
      foreach ($important_dates[$i]->details as $term_date) {

        $term_date = [
          'term' => $term_date->termName,
          'start_date' => date("M j, Y", strtotime($term_date->startDate)),
          'end_date' => $term_date->endDate == NULL ? NULL : date("M j, Y", strtotime($term_date->endDate)),
        ];

        $term_dates[] = $term_date;
      }

      // Counter to be used in keywords.
      $keyword_count = 0;

      // Reset the keywords string, so that we only get the important
      // date that we are on.
      $keywords = '';

      // Step through each of the keywords and setup variable.
      foreach ($important_dates[$i]->keywords as $keyword) {

        if ($keyword_count > 0) {
          $keywords .= ', ' . $keyword;
        }
        else {
          $keywords .= $keyword;
        }

        $keyword_count++;
      }

      // Counter to be used in keywords.
      $audience_count = 0;

      // Reset the keywords string, so that we only get the important
      // date that we are on.
      $audiences = '';

      // Step through each of the keywords and setup variable for audiences.
      foreach ($important_dates[$i]->audiences as $audience) {

        if ($audience_count > 0) {
          $audiences .= ', ' . $audience;
        }
        else {
          $audiences .= $audience;
        }

        $audience_count++;
      }

      // The date entry.
      $date_entry = [
        'content' => $content,
        'title' => $important_dates[$i]->name,
        'type' => $important_dates[$i]->importantDateType,
        'term_dates' => $term_dates,
        'keywords' => $keywords,
        'audiences' => $audiences,
      ];

      // The completed important date entry.
      $dates[] = $date_entry;
    }

    // Return the render array.
    return [
      '#theme' => 'uw_important_dates_block',
      '#dates' => $dates,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // Set the options for maximum number of items.
    // API states that max is 25.
    $options = [];
    for ($i = 1; $i <= 25; $i++) {
      $options[$i] = $i;
    }

    // The form element for the maximum number of items.
    $max_items = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of UW events to display'),
      '#description' => $this->t('Select the number of items to show for UW events'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['max_items']) ?: 15,
    ];

    // The form element for the maximum number of characters.
    $max_chars = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of characters to display'),
      '#description' => $this->t('Enter the number of characters to show in the content of the news.  Range is from 100 - 600.'),
      '#default_value' => isset($this->configuration['max_chars']) ?: 400,
    ];

    // The completed form.
    $form = [
      'max_items' => $max_items,
      'max_chars' => $max_chars,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    // Get the values from the form state.
    $values = $form_state->getValues();

    // Ensure that the maximum number of characters is a number.
    if (!is_numeric($values['max_chars'])) {

      // Set error that maximum number of characters must be a number.
      $form_state->setErrorByName('max_chars', $this->t('Maximum number of characters must be a number.'));
    }

    // If the maximum number of characters is a number,
    // ensure between 100 and 600.
    else {
      if ($values['max_chars'] < 100 || $values['max_chars'] > 600) {
        $form_state->setErrorByName('max_chars', $this->t('Maximum number of characters must be between 100 and 600.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    // Load in the values from the form_sate.
    $values = $form_state->getValues();

    // Set the config for block.
    $this->configuration['max_items'] = $values['max_items'];
    $this->configuration['max_chars'] = $values['max_chars'];
  }

}
