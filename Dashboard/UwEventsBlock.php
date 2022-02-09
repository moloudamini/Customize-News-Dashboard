<?php

namespace Drupal\uw_dashboard\Plugin\Block;

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
 *   id = "uw_news_block_events",
 *   admin_label = @Translation("UW Events"),
 * )
 */
class UwEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $url = 'https://openapi.data.uwaterloo.ca/v3/Wcms/latestevents/' . $this->configuration['max_items'];

    // Make the call to the API.
    $events = $this->httpClient->get($url, [
      'headers' => [
        'X-API-KEY' => settings::get('open_data_api_key'),
      ],
    ]);

    // Decode the API call.
    $events = json_decode($events->getBody());

    // Step through each of the entries in the response and setup
    // to display correctly.
    foreach ($events as $entry) {

      // Strip all the tags from the content.
      $content = strip_tags(
        $entry->content,
        ['<br>', '<p>']
      );

      // Truncate the content based on config from form.
      $content = Unicode::truncate(
        $content,
        $this->configuration['max_chars'],
        NULL,
        TRUE
      );

      // Setup the array for the event.
      $event = [
        'title' => $entry->title,
        'start_date' => date("M j, Y", strtotime($entry->eventStartDate)),
        'end_date' => date("M j, Y", strtotime($entry->eventEndDate)),
        'url' => $entry->itemUri,
        'type' => $entry->eventType,
        'content' => $content,
      ];

      // Put the event into the uwevents array for the template
      // to use.
      $uwevents[] = $event;
    }

    // Return the render array.
    return [
      '#theme' => 'uw_events_block',
      '#uwevents' => $uwevents,
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
