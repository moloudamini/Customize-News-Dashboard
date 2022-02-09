<?php

namespace Drupal\uw_dashboard\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;

/**
 * UW News Global News block.
 *
 * @Block(
 *   id = "uw_news_block_global",
 *   admin_label = @Translation("Global News"),
 * )
 */
class UwNewsGlobalBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    // The array to store the news.
    $global_news = [];

    // Get the URL for the API call based on the filter type.
    switch ($this->configuration['global_news_filter']) {

      // Setup the URL and filter for the category filter.
      case 'category':

        // The URL for the API for the category filter.
        $url = 'http://newsapi.org/v2/top-headlines?category=' . $this->configuration['category'];

        // The array needed to display the category filter.
        $filter = [
          'type' => 'Category',
          'filter' => $this->configuration['category_name'],
        ];
        break;

      // Setup the URL and filter for the country filter.
      case 'country':

        // The URL for the API for the country filter.
        $url = 'https://newsapi.org/v2/top-headlines?country=' . $this->configuration['country'];

        // The array needed to display the country filter.
        $filter = [
          'type' => 'Country',
          'filter' => $this->configuration['country_name'],
        ];
        break;

      case 'keywords':

        // The URL for the API for the keywords, title and body.
        $url = 'https://newsapi.org/v2/everything?q=' . UrlHelper::encodePath($this->configuration['keywords']);

        // The array needed to display the filter for keywords, title and body.
        $filter = [
          'type' => 'Keywords (title and body)',
          'filter' => $this->configuration['keywords'],
        ];

        break;

      case 'keywords_title':

        // The URL for the API for the keywords, title only.
        $url = 'https://newsapi.org/v2/everything?qInTitle=' . UrlHelper::encodePath($this->configuration['keywords_title']);

        // The array needed to display the filter for keywords, title only.
        $filter = [
          'type' => 'Keywords (title only)',
          'filter' => $this->configuration['keywords_title'],
        ];

        break;

      // Setup the URL and filter for the source filter.
      case 'source':

        // The URL for the API for the source filter.
        $url = 'https://newsapi.org/v2/top-headlines?sources=' . $this->configuration['source'];

        // The array needed to display the source filter.
        $filter = [
          'type' => 'Source',
          'filter' => $this->configuration['source_name'],
        ];
        break;
    }

    // If there is a specific language set, then add it to API call.
    if ($this->configuration['language'] !== 'all') {
      $url .= '&language=' . $this->configuration['language'];
    }

    // Set the maximum number of items to the API call.
    $url .= '&pageSize=' . $this->configuration['max_items'];

    // Set the sort by.
    $url .= '&sortBy=' . $this->configuration['sort_by'];

    // Make the call to the API.
    $news = $this->httpClient->get($url, [
      'headers' => [
        'X-API-KEY' => settings::get('news_api_key'),
      ],
    ]);

    // Decode the API call.
    $news = json_decode($news->getBody())->articles;

    // Step through each of the dates and setup the correct display date.
    foreach ($news as $key => $article) {
      $news[$key]->date = date('M j, Y', strtotime($article->publishedAt));
      $news[$key]->time = date('g:h a T', strtotime($article->publishedAt));
    }

    // Setup the array for the global news template.
    $global_news = [
      'filter' => $filter,
      'language' => $this->configuration['language_name'],
      'news' => $news,
    ];

    // Return the render array.
    return [
      '#theme' => 'uw_news_global_block',
      '#global_news' => $global_news,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // The array of form elements.
    $form = [];

    // The filter type form element.
    $global_news_filter = [
      '#type' => 'select',
      '#title' => $this->t('Filter news'),
      '#description' => $this->t('Select the way you would to filter the news by.'),
      '#options' => [
        'category' => $this->t('Category'),
        'country' => $this->t('Country'),
        'keywords' => $this->t('Keywords (title and body)'),
        'keywords_title' => $this->t('Keywords (title only)'),
        'source' => $this->t('Source'),
      ],
      '#empty_option' => $this->t('- Select a value -'),
      '#default_value' => $this->configuration['global_news_filter'] ?? '',
      '#required' => TRUE,
    ];

    // The form element for the category.
    $category = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#description' => $this->t('Select the category to get the news from.'),
      '#empty_option' => $this->t('- Select a value -'),
      '#options' => $this->getGlobalNewsOptions('category'),
      '#default_value' => $this->configuration['category'] ?? '',
      '#states' => [
        'visible' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'category']],
        ],
        'required' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'category']],
        ],
      ],
    ];

    // The form element for the country.
    $country = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#description' => $this->t('Select the country to get the news from.'),
      '#empty_option' => $this->t('- Select a value -'),
      '#options' => $this->getGlobalNewsOptions('country'),
      '#default_value' => $this->configuration['country'] ?? '',
      '#states' => [
        'visible' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'country']],
        ],
        'required' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'country']],
        ],
      ],
    ];

    // The form element for the keywords, title and body.
    $keywords = [
      '#type' => 'textfield',
      '#title' => $this->t('Keywords in title and body'),
      '#description' => $this->t('
        Enter keywords to search for news in both the title and body.<br /><br />
        Advanced search is supported here:

        <ul>
            <li>Surround phrases with quotes (") for exact match.</li>
            <li>Prepend words or phrases that must appear with a + symbol. Eg: +bitcoin</li>
            <li>Prepend words that must not appear with a - symbol. Eg: -bitcoin</li>
            <li>Alternatively you can use the AND / OR / NOT keywords, and optionally group these with parenthesis. Eg: crypto AND (ethereum OR litecoin) NOT bitcoin.</li>
        </ul>
      '),
      '#default_value' => $this->configuration['keywords'] ?? '',
      '#states' => [
        'visible' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'keywords']],
        ],
        'required' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'keywords']],
        ],
      ],
    ];

    // The form element for the keywords, title only.
    $keywords_title = [
      '#type' => 'textfield',
      '#title' => $this->t('Keywords in title only'),
      '#description' => $this->t('
        Enter keywords to search for news in the title only.<br /><br />
        Advanced search is supported here:

        <ul>
            <li>Surround phrases with quotes (") for exact match.</li>
            <li>Prepend words or phrases that must appear with a + symbol. Eg: +bitcoin</li>
            <li>Prepend words that must not appear with a - symbol. Eg: -bitcoin</li>
            <li>Alternatively you can use the AND / OR / NOT keywords, and optionally group these with parenthesis. Eg: crypto AND (ethereum OR litecoin) NOT bitcoin.</li>
        </ul>
      '),
      '#default_value' => $this->configuration['keywords_title'] ?? '',
      '#states' => [
        'visible' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'keywords_title']],
        ],
        'required' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'keywords_title']],
        ],
      ],
    ];

    // The form element for the sources.
    $source = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#description' => $this->t('Select the source to get the news from.'),
      '#empty_option' => $this->t('- Select a value -'),
      '#options' => $this->getGlobalNewsOptions('source'),
      '#default_value' => $this->configuration['source'] ?? '',
      '#states' => [
        'visible' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'source']],
        ],
        'required' => [
          ['select[name="settings[filters][global_news_filter]"]' => ['value' => 'source']],
        ],
      ],
    ];

    // The collapsible details tab for the filters.
    $filters = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#open' => TRUE,
      'global_news_filter' => $global_news_filter,
      'category' => $category,
      'country' => $country,
      'keywords' => $keywords,
      'keywords_title' => $keywords_title,
      'source' => $source,
    ];

    // The form element for sort by.
    $sort_by = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#description' => $this->t('Select the way to sort the news.'),
      '#options' => [
        'relevancy' => $this->t('Articles more closely related to keywords come first'),
        'popularity' => $this->t('Articles from popular sources and publishers come first'),
        'publishedAt' => $this->t('Newest articles come first'),
      ],
      '#default_value' => $this->configuration['sort_by'] ?? 'publishedAt',
      '#required' => TRUE,
    ];

    // The form element for the language.
    $language = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Select the language to get the news from.'),
      '#options' => $this->getGlobalNewsOptions('language'),
      '#default_value' => $this->configuration['language'] ?? '',
      '#required' => TRUE,
    ];

    // The form element for the maximum number of items.
    $max_items = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of news items to display'),
      '#description' => $this->t('Select the number of items to be displayed.'),
      '#options' => $this->getGlobalNewsOptions('max_items'),
      '#default_value' => $this->configuration['max_items'] ?? 10,
      '#required' => TRUE,
    ];

    // The collapsible details tab for the settings.
    $news_settings = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
      'sort_by' => $sort_by,
      'language' => $language,
      'max_items' => $max_items,
    ];

    // Fill in the complete form element.
    $form = [
      'filters' => $filters,
      'news_settings' => $news_settings,
    ];

    return $form;
  }

  /**
   * A function to get the options for the global news block.
   *
   * @param string $type
   *   The type of options to get.
   *
   * @return array
   *   The array of options.
   */
  public function getGlobalNewsOptions(string $type): array {

    // Get the options based on the type sent.
    switch ($type) {

      // The category type of options.
      case 'category':

        // The category options.
        $options = [
          'business' => $this->t('Business'),
          'entertainment' => $this->t('Entertainment'),
          'general' => $this->t('General'),
          'health' => $this->t('Health'),
          'science' => $this->t('Science'),
          'sports' => $this->t('Sports'),
          'technology' => $this->t('Technology'),
        ];

        break;

      // The country type of options.
      case 'country':

        // The options for country, provided from newsapi.org at:
        // https://newsapi.org/docs/endpoints/top-headlines.
        $options = [
          'ar' => $this->t('Argentina'),
          'au' => $this->t('Australia'),
          'be' => $this->t('Belgium'),
          'br' => $this->t('Brazil'),
          'bg' => $this->t('Bulgaria'),
          'ca' => $this->t('Canada'),
          'cn' => $this->t('China'),
          'co' => $this->t('Colombia'),
          'cu' => $this->t('Cuba'),
          'cz' => $this->t('Czechia'),
          'de' => $this->t('Germany'),
          'eg' => $this->t('Egypt'),
          'fr' => $this->t('France'),
          'gr' => $this->t('Greece'),
          'hk' => $this->t('Hong Kong'),
          'hu' => $this->t('Hungary'),
          'id' => $this->t('Indonesia'),
          'ie' => $this->t('Ireland'),
          'il' => $this->t('Israel'),
          'in' => $this->t('India'),
          'it' => $this->t('Italy'),
          'jp' => $this->t('Japan'),
          'lv' => $this->t('Latvia'),
          'lt' => $this->t('Lithuania'),
          'my' => $this->t('Malaysia'),
          'mx' => $this->t('Mexico'),
          'ma' => $this->t('Morocco'),
          'nl' => $this->t('Netherlands'),
          'ng' => $this->t('Nigeria'),
          'no' => $this->t('Norway'),
          'ph' => $this->t('Philippines'),
          'pl' => $this->t('Poland'),
          'pt' => $this->t('Portugal'),
          'ro' => $this->t('Romania'),
          'ru' => $this->t('Russian Federation'),
          'sa' => $this->t('Saudi Arabia'),
          'rs' => $this->t('Serbia'),
          'sg' => $this->t('Singapore'),
          'sk' => $this->t('Slovakia'),
          'si' => $this->t('Slovenia'),
          'za' => $this->t('South Africa'),
          'kr' => $this->t('South Korea'),
          'se' => $this->t('Sweden'),
          'ch' => $this->t('Switzerland'),
          'tw' => $this->t('Taiwan'),
          'th' => $this->t('Thailand'),
          'tr' => $this->t('Turkey'),
          'ua' => $this->t('Ukraine'),
          'ae' => $this->t('United Arab Emirates'),
          'gb' => $this->t('United Kingdom'),
          'us' => $this->t('United States of America'),
          've' => $this->t('Venezuela (Bolivarian Republic)'),
        ];

        break;

      // The language type of options.
      case 'language':

        // The options for language, provided from newsapi.org at:
        // https://newsapi.org/docs/endpoints/everything.
        $options = [
          'all' => $this->t('All'),
          'ar' => $this->t('Arabic'),
          'zh' => $this->t('Chinese'),
          'nl' => $this->t('Dutch'),
          'en' => $this->t('English'),
          'fr' => $this->t('French'),
          'de' => $this->t('German'),
          'he' => $this->t('Hebrew'),
          'it' => $this->t('Italian'),
          'no' => $this->t('Norwegian'),
          'pt' => $this->t('Portuguese'),
          'ru' => $this->t('Russian'),
          'se' => $this->t('Sami'),
        ];

        break;

      // The maximum number of items options.
      case 'max_items':

        // Set the options for the maximum number of items.
        for ($i = 0; $i <= 100; $i++) {
          $options[$i] = $i;
        }

        break;

      // The source type of options.
      case 'source':

        // The URL to get the sources from the API.
        $url = 'http://newsapi.org/v2/sources';

        // Make the call to the API.
        $sources = $this->httpClient->get($url, [
          'headers' => [
            'X-API-KEY' => settings::get('news_api_key'),
          ],
        ]);

        // Decode the API call.
        $sources = json_decode($sources->getBody())->sources;

        // The options for the sources.
        $options = [];

        // Step through each of the sources and set the options for the
        // select element.
        foreach ($sources as $source) {
          $options[$source->id] = $source->name;
        }

        break;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    // Load in the values from the form_sate.
    $values = $form_state->getValues();

    // Set value for the type of filter.
    $this->configuration['global_news_filter'] = $values['filters']['global_news_filter'];

    // Set values for the category and category name.
    $this->configuration['category'] = $values['filters']['category'];
    if ($values['filters']['category'] !== '') {
      $this->configuration['category_name'] = $form['settings']['filters']['category']['#options'][$values['filters']['category']]->__toString();
    }

    // Set values for the country and country name.
    $this->configuration['country'] = $values['filters']['country'];
    if ($values['filters']['country'] !== '') {
      $this->configuration['country_name'] = $form['settings']['filters']['country']['#options'][$values['filters']['country']]->__toString();
    }

    // Set values for the keywords, title and body.
    $this->configuration['keywords'] = $values['filters']['keywords'];

    // Set values for the keywords, title only.
    $this->configuration['keywords_title'] = $values['filters']['keywords_title'];

    // Set the values for the source and the source name.
    $this->configuration['source'] = $values['filters']['source'];
    if ($values['filters']['source'] !== '') {
      $this->configuration['source_name'] = $form['settings']['filters']['source']['#options'][$values['filters']['source']];
    }

    // Set values for the language and language name.
    $this->configuration['language'] = $values['news_settings']['language'];
    $this->configuration['language_name'] = $form['settings']['news_settings']['language']['#options'][$values['news_settings']['language']]->__toString();

    // Set values for the sort by.
    $this->configuration['sort_by'] = $values['news_settings']['sort_by'];

    // Set values for the maximum number of items.
    $this->configuration['max_items'] = $values['news_settings']['max_items'];
  }

}
