<?php

namespace Drupal\uw_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * UW Stories News Block.
 *
 * @Block(
 *   id = "uw_news_block_uwstories",
 *   admin_label = @Translation("UW Stories"),
 * )
 */
class UwStoriesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    // URL to stories API.
    $url = 'https://pilots.uwaterloo.ca/news/api/v1.0/news?sort=-date&range=' . $this->configuration['max_stories'];

    // The list of filters with full name.
    $filter_list = '';

    // These are the filters to check.
    $filters_to_check = [
      'topics',
      'audience',
      'faculties',
    ];

    // Step through each filter to check and see if we need to add
    // to API call and the filter list.
    foreach ($filters_to_check as $filter_to_check) {

      // If there are things in filter, then setup API URL and filter list.
      if (count($this->configuration[$filter_to_check]) > 0) {

        // Add the filter to the API URL.
        $url .= '&filter[' . $filter_to_check . ']=' . $this->buildFilter($this->configuration[$filter_to_check]);

        // If there are already filters in the list add a comma.
        if ($filter_list !== '') {
          $filter_list .= ', ';
        }

        // Get the filter names and put it into the filter list string.
        $filter_list .= $this->getFilterName($filter_to_check, $this->configuration[$filter_to_check]);
      }
    }

    // If there is a news type filter, add to API URL and filter list.
    if ($this->configuration['story_type']) {

      // Add to API URL.
      $url .= '&filter[type]=' . $this->configuration['story_type'];

      // If there are already filters in the list add a comma.
      if ($filter_list !== '') {
        $filter_list .= ', ';
      }

      // Get the filter names and put it into the filter list string.
      $filter_list .= $this->getFilterName('news_type', [$this->configuration['story_type']]);
    }

    // Set the filter list.
    $stories['filter_list'] = $filter_list;

    // Make the call to the API.
    $stories_api = $this->httpClient->get($url, []);

    // Decode the API call.
    $stories['stories'] = json_decode($stories_api->getBody())->data;

    // Return the block to theme.
    return [
      '#theme' => 'uw_stories_block',
      '#stories' => $stories,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // The actual topics filter.
    $topics = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Topics'),
      '#options' => $this->getOptions('topics'),
      '#default_value' => isset($this->configuration['topics']) ?: '',
    ];

    // The collapsible details tab for the topics filter.
    $topics_list = [
      '#type' => 'details',
      '#title' => $this->t('Filter by topics'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['uw-news-topics-details'],
      ],
      'topics' => $topics,
    ];

    // The actual faculties filter.
    $faculties = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Faculties'),
      '#options' => $this->getOptions('faculties'),
      '#default_value' => isset($this->configuration['faculties']) ?: '',
    ];

    // The collapsible details tab for the faculties filter.
    $faculties_list = [
      '#type' => 'details',
      '#title' => $this->t('Filter by faculties'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['uw-news-faculties-details'],
      ],
      'faculties' => $faculties,
    ];

    // The actual audience filter.
    $audience = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Audience'),
      '#options' => $this->getOptions('audience'),
      '#default_value' => isset($this->configuration['audience']) ?: '',
    ];

    // The collapsible details tab for the audience filter.
    $audience_list = [
      '#type' => 'details',
      '#title' => $this->t('Filter by audience'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['uw-news-audience-details'],
      ],
      'audience' => $audience,
    ];

    // The actual story type filter.
    $story_type = [
      '#type' => 'select',
      '#title' => $this->t('Story type'),
      '#options' => $this->getOptions('news_type'),
      '#default_value' => isset($this->configuration['story_type']) ?: '',
      '#empty_option' => $this->t('- Select a value'),
    ];

    // The collapsible details tab for the story type filter.
    $story_type_list = [
      '#type' => 'details',
      '#title' => $this->t('Filter by story type'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['uw-news-story-type-details'],
      ],
      'story_type' => $story_type,
    ];

    // The array for the maximum number of stories options.
    $options = [];

    // Setup the options for the maximum number of stories.
    for ($i = 1; $i <= 25; $i++) {
      $options[$i] = $i;
    }

    // The maximum number of stories form element.
    $max_stories = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of stories'),
      '#description' => $this->t('Select the maximum number of stories to be displayed.'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['max_stories']) ?: '15',
    ];

    // The completed form.
    $form = [
      'topics_list' => $topics_list,
      'faculties_list' => $faculties_list,
      'audience_list' => $audience_list,
      'story_type_list' => $story_type_list,
      'max_stories' => $max_stories,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    // Load in the values from the form_sate.
    $values = $form_state->getValues();

    // Set the config for all the filters.
    $this->configuration['topics'] = $this->getOptionValues($values['topics_list']['topics']);
    $this->configuration['faculties'] = $this->getOptionValues($values['faculties_list']['faculties']);
    $this->configuration['audience'] = $this->getOptionValues($values['audience_list']['audience']);
    $this->configuration['story_type'] = $values['story_type_list']['story_type'];
    $this->configuration['max_stories'] = $values['max_stories'];
  }

  /**
   * Function to get the options from the stories API.
   *
   * @param string $type
   *   The type of option to get (topics, audience, etc.).
   *
   * @return array
   *   An array of options to be used in a form element.
   */
  public function getOptions(string $type): array {

    // The array for the options.
    $options = [];

    // URL to topics API.
    $url = 'https://pilots.uwaterloo.ca/news/api/v1.0/' . $type;

    // Make the call to the API.
    $filters = $this->httpClient->get($url, []);

    // Decode the API call.
    $filters = json_decode($filters->getBody())->data;

    // Step through each of the topics and set the option.
    foreach ($filters as $filter) {
      $options[$filter->id] = $filter->label;
    }

    // Return the options array.
    return $options;
  }

  /**
   * A function to get the values from a form element.
   *
   * @param array $option_values
   *   The list of options from the form_state.
   *
   * @return array
   *   An array of the options that are selected.
   */
  public function getOptionValues(array $option_values) {

    // The array of values to be returned.
    $return_values = [];

    // Step through each of the options and see if there are values.
    foreach ($option_values as $option_value) {

      // If there is a value, meaning non-zero, then add it to the
      // return values array.
      if ($option_value !== 0) {
        $return_values[] = $option_value;
      }
    }

    // Return the values, if there are none, array will be blank.
    return $return_values;
  }

  /**
   * A function to return the comma separated list for filters.
   *
   * @param array $filter
   *   The filter to be built.
   *
   * @return string
   *   The comma delimited string of filters.
   */
  public function buildFilter(array $filter) {

    // Counter used for adding commas in filter.
    $counter = 0;

    // The actual string for the filter.
    $filter_string = '';

    // Step through the filter and setup string for filter.
    foreach ($filter as $f) {

      // If we are not on the first filter, add the comma.
      if ($counter > 0) {
        $filter_string .= ',';
      }

      $filter_string .= $f;
      $counter++;
    }

    // Return the string to be used in the filter API call.
    return $filter_string;
  }

  /**
   * A function to get a comma delimited string of filter names.
   *
   * @param string $filter_name
   *   The name of the filter.
   * @param array $filter_ids
   *   An array of of the ids for the filters.
   *
   * @return string
   *   The comma delimited string of real names of the filters.
   */
  public function getFilterName(string $filter_name, array $filter_ids) {

    // The counter to be used to see if adding the comma.
    $counter = 0;

    // The string of list of filters.
    $filter_list = '';

    // Step through each filter id and get the real name.
    foreach ($filter_ids as $filter_id) {

      // The API URL.
      $url = 'https://pilots.uwaterloo.ca/news/api/v1.0/' . $filter_name . '/' . $filter_id;

      // Make the API call.
      $filter_api = $this->httpClient->get($url, []);

      // Decode the call from the API.
      $filter_api = json_decode($filter_api->getBody())->data;

      // If we have more than one filter, add the comma to the string.
      if ($counter > 0) {
        $filter_list .= ', ';
      }

      // Add the real name of the filter to the list.
      $filter_list .= $filter_api[0]->label;

      // Increment the counter so we set the comma correctly.
      $counter++;
    }

    // Return the comma delimited list of filter names.
    return $filter_list;
  }

}
