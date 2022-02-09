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
 * UW Custom Block Multi Type List block.
 *
 * @Block(
 *   id = "uw_news_block_uwnews",
 *   admin_label = @Translation("UW News"),
 * )
 */
class UwNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    if ($this->configuration['filter'] == 'all') {
      // The URL for the API call, have to send the max number
      // of items to be sent, which comes from config for the block.
      $url = 'https://openapi.data.uwaterloo.ca/v3/Wcms/latestnews/' . $this->configuration['max_items'];
    }
    else {
      $url = 'https://openapi.data.uwaterloo.ca/v3/Wcms/' . $this->configuration['filter'] . '/news?newestFirst=true&maxItems=' . $this->configuration['max_items'];
    }

    // Make the call to the API.
    $news = $this->httpClient->get($url, [
      'headers' => [
        'X-API-KEY' => settings::get('open_data_api_key'),
      ],
    ]);

    // Decode the API call.
    $news = json_decode($news->getBody());

    // Step through each of the entries in the response and setup
    // to display correctly.
    foreach ($news as $key => $entry) {

      // Set the date to be in ShortMonth day, Year.
      $news[$key]->postedDate = date("M j, Y", strtotime($entry->postedDate));

      // Strip all tags from the content except for breaks and
      // paragraphs.
      $news[$key]->content = strip_tags(
        $news[$key]->content,
        ['<br>', '<p>']
      );

      // Truncate the text and add ellipsis on the content.
      $news[$key]->content = Unicode::truncate(
        $news[$key]->content,
        $this->configuration['max_chars'],
        NULL,
        TRUE
      );

      // Fix any faulty tags on the content.
      $news[$key]->content = Html::normalize($news[$key]->content);
    }

    // If the filter is not all, get the site name from the API.
    if ($this->configuration['filter'] !== 'all') {

      // The URL to get the info about the site filter.
      $url = 'https://openapi.data.uwaterloo.ca/v3/Wcms/' . $this->configuration['filter'];

      // Make the call to the API.
      $site = $this->httpClient->get($url, [
        'headers' => [
          'X-API-KEY' => settings::get('open_data_api_key'),
        ],
      ]);

      // Decode the API call.
      $site = json_decode($site->getBody());

      // Get the site name from the decoded API.
      $site = $site->name;
    }

    // The filter is all so site the name to displayed as All.
    else {
      $site = 'All';
    }

    // Site the variables for the site name (filter) and the actual
    // news content.
    $uwnews['news'] = $news;
    $uwnews['filter'] = $site;

    // Return the render array.
    return [
      '#theme' => 'uw_news_block',
      '#uwnews' => $uwnews,
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

    // The filter by faculty/department.
    $filter = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#description' => $this->t('Select the filter to be applied for UW news.'),
      '#options' => [
        'all' => $this->t('All'),
        '39' => $this->t('Arts'),
        '142' => $this->t('Engineering'),
        '245' => $this->t('Environment'),
        '1240' => $this->t('Graduate Studies and Postdoctoral Affairs'),
        '11' => $this->t('Health'),
        '286' => $this->t('Mathematics'),
        '473' => $this->t("Registrar's Office"),
        '287' => $this->t('Science'),
      ],
      '#default_value' => isset($this->configuration['filter']) ?: 'all',
    ];

    // The form element for the maximum number of items.
    $max_items = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of UW news items to display'),
      '#description' => $this->t('Select the number of items to show for UW news'),
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

    $form = [
      'filter' => $filter,
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
    $this->configuration['filter'] = $values['filter'];
    $this->configuration['max_items'] = $values['max_items'];
    $this->configuration['max_chars'] = $values['max_chars'];
  }

}
