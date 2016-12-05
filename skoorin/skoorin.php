<?php
/*
Plugin Name:  Skoorin
Plugin URI:   http://skoorin.com
Description:  Embed skoorin.com content into wordpress
Version:      2.0.0
Author:       Ivar Oja
Author URI:   http://ips.re
License:      Apache License 2.0
License URI:  https://www.apache.org/licenses/LICENSE-2.0.txt
Text Domain:  skoorin
Domain Path:  /languages
*/

if (!defined('ABSPATH'))
  exit;

require_once 'skoorin-settings.php';

class Skoorin {
  function __construct() {
    $this->ver = '2.0.0';
    $this->options = get_option('skoorin_options', Skoorin_Settings::get_default_options());
    $this->defaults = array(
      'shortcode_results' => array(
        'competition_id' => 0,
        'player' => 'all',
        'class' => 'all',
        'group' => 'all'
      )
    );
    $this->l10n = array(
      'results' => array(
        'all' => array(
          'player' => __('All players', 'skoorin'),
          'class' => __('All classes', 'skoorin'),
          'group' => __('All groups', 'skoorin')
        ),
        'hole' => __('Hole', 'skoorin'),
        'par' => __('Par', 'skoorin'),
        'tot' => __('Tot', 'skoorin'),
        'to_par' => __('To par', 'skoorin'),
        'extra' => array(
          'bullseye_hit' => __('Bullseye hit', 'skoorin'),
          'green_hit' => __('Green hit', 'skoorin'),
          'outside_circle_putt' => __('Outside circle putt', 'skoorin'),
          'inside_circle_putts' => __('Inside circle putt', 'skoorin'),
          'inside_bullseye_putts' => __('Inside bullseye putt', 'skoorin'),
          'penalty' => __('Penalty', 'skoorin')
        )
      ),
      // 'registration_list' => array(),
      // 'registration_form' => array()
    );

    add_shortcode('skoorin_results', array($this, 'shortcode_results'));
    // add_shortcode('skoorin_registration_list', array($this, 'shortcode_registration_list'));
    // add_shortcode('skoorin_registration_form', array($this, 'shortcode_registration_form'));

    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_public'), 99);

    /**
     * @todo Temp.
     */
    add_action('wp_ajax_skoorin_get_results', array($this, 'ajax_get_results'));
    add_action('wp_ajax_nopriv_skoorin_get_results', array($this, 'ajax_get_results'));
    // remove_action('wp_head', 'print_emoji_detection_script', 7);
  }

  function enqueue_scripts_public() {
    wp_register_style('skoorin-results', plugins_url('styles/skoorin-results.css', __FILE__), array(), $this->ver);
    wp_register_script('skoorin-results', plugins_url('scripts/skoorin-results.js', __FILE__), array('jquery'), $this->ver, true);
    wp_localize_script('skoorin-results', 'skoorinResults', array(
      'l10n' => $this->l10n['results'],
      'ajax_url' => admin_url('admin-ajax.php')
    ));
    // wp_register_style('skoorin-registration-list', plugins_url('styles/skoorin-registration-list.css', __FILE__));
    // wp_register_script('skoorin-registration-list', plugins_url('scripts/skoorin-registration-list.js', __FILE__), array(), $this->ver, true);
    // wp_register_style('skoorin-registration-form', plugins_url('styles/skoorin-registration-form.css', __FILE__));
    // wp_register_script('skoorin-registration-form', plugins_url('scripts/skoorin-registration-form.js', __FILE__), array(), $this->ver, true);
  }
  
  function shortcode_results($attributes) {
    $atts = shortcode_atts($this->defaults['shortcode_results'], $attributes);

    if (!is_numeric($atts['competition_id']))
      return '';
    
    wp_enqueue_style('skoorin-results');
    wp_enqueue_script('skoorin-results');
    extract($this->options);

    $output = "<div class='skoorin-results' data-competition-id='$atts[competition_id]'>";

      /* filter */
      if (is_array($results_filter) && count($results_filter)) {
        $filters = self::api_get(array(
          'content' => 'wordpress_filters',
          'competition_id' => $atts['competition_id']
        ));

        $output.= '<div class="skoorin-results-filter">';

        foreach ($results_filter as $filter_name)
          $output.= call_user_func(
            get_class()."::get_{$filter_name}_filter",
            $filters,
            $atts,
            $this->l10n['results']
          );

        $output.= '</div>';
      }

      $results = self::api_get(array(
        'content' => 'result_json',
        'id' => $atts['competition_id']
      ));

      /* results table */
      $table_wrapper_class = 'skoorin-results-table-container'.(
        isset($responsive_table) && $responsive_table
          ? ' table-scroll table-responsive'
          : ''
      );
      $output.= "<div class='skoorin-results-table'><div class='$table_wrapper_class'>";
      $output.= $this->get_results_table($results, $this->l10n['results']);
      $output.= '</div></div>';

      /* data for js */
      $output.= '<script type="application/json" class="skoorin-results-data">';
      $output.= json_encode(array(
        'filters_selected' => $results_filter,
        'filters' => $filters,
        'results' => $results
      ));
      $output.= '</script>';
    
    $output.= '</div>';

    return $output;
  }
  
  /*function shortcode_registration_list($attributes) {
    $atts = shortcode_atts(array(
      'competition_id' => 0
    ), $attributes);
  }
  
  function shortcode_registration_form($attributes) {
    $atts = shortcode_atts(array(
      'competition_id' => 0
    ), $attributes);
  }*/

  function ajax_get_results() {
    sleep(1);
    echo json_encode(self::get_competition_json($_POST['id']));
    exit;
  }

  public static function api_get($params, $res_type = 'json', $url = 'https://skoorin.com/api.php') {
    if (!is_array($params))
      return null;

    /**
     * @todo Temp.
     */
    if ($params['content'] == 'result_json')
      return self::get_competition_json($params['id']);

    if ($params['content'] == 'wordpress_filters')
      return [
        'player' => [
          [
            'id' => 6216,
            'name' => 'Silver Saks'
          ], [
            'id' => 1,
            'name' => 'Marko Saviauk'
          ], [
            'id' => 5564,
            'name' => 'Anette Ojastu'
          ], [
            'id' => 19443,
            'name' => 'Ivar Oja'
          ]
        ],
        'class' => [
          [
            'id' => 'mehed',
            'name' => 'Mehed'
          ], [
            'id' => 'naised',
            'name' => 'Naised'
          ]
        ],
        'group' => [
          ['id' => 1, 'name' => '1'],
          ['id' => 2, 'name' => '2'],
          ['id' => 3, 'name' => '3'],
          ['id' => 4, 'name' => '4'],
          ['id' => 5, 'name' => '5'],
          ['id' => 6, 'name' => '6'],
          ['id' => 7, 'name' => '7'],
          ['id' => 8, 'name' => '8'],
          ['id' => 9, 'name' => '9'],
          ['id' => 10, 'name' => '10'],
          ['id' => 11, 'name' => '11'],
          ['id' => 12, 'name' => '12'],
          ['id' => 13, 'name' => '13'],
          ['id' => 14, 'name' => '14'],
          ['id' => 15, 'name' => '15'],
          ['id' => 16, 'name' => '16'],
          ['id' => 17, 'name' => '17'],
          ['id' => 18, 'name' => '18'],
          ['id' => 19, 'name' => '19'],
          ['id' => 20, 'name' => '20'],
          ['id' => 21, 'name' => '21'],
          ['id' => 22, 'name' => '22'],
          ['id' => 23, 'name' => '23'],
          ['id' => 24, 'name' => '24'],
          ['id' => 25, 'name' => '25'],
          ['id' => 26, 'name' => '26'],
          ['id' => 27, 'name' => '27'],
          ['id' => 28, 'name' => '28'],
          ['id' => 29, 'name' => '29'],
          ['id' => 30, 'name' => '30'],
          ['id' => 31, 'name' => '31'],
          ['id' => 32, 'name' => '32'],
          ['id' => 33, 'name' => '33'],
          ['id' => 34, 'name' => '34'],
          ['id' => 35, 'name' => '35'],
          ['id' => 36, 'name' => '36']
        ],
        'competition' => [
          'id' => 367748,
          'name' => 'Väike võistlus',
          'children' => [
            [
              'id' => 367747,
              'name' => '1. ring'
            ], [
              'id' => 367749,
              'name' => '2. ring'
            ]
          ]
        ]
      ];

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url.'?'.http_build_query($params),
      CURLOPT_RETURNTRANSFER => true
    ));

    $response = curl_exec($curl);

    switch ($res_type) {
      case 'html':
        if (!$response)
          return 'Error: "'.curl_error($curl).'". Code: '.curl_errno($curl);

        $output = $response;
        break;
      
      case 'json':
        if (!$response)
          return array(
            'error' => curl_error($curl),
            'code' => curl_errno($curl)
          );

        $output = json_decode($response);
    }
    
    curl_close($curl);

    return $output;
  }

  static function get_competition_json($competition_id) {
    $competition_id = (int) $competition_id;

    switch ($competition_id) {
      case 367748:
        return [
          'par_total' => 54,
          'holes' => [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
          'players' => [
            [
              'id' => 6216,
              'name' => 'Silver Saks',
              'class' => 'mehed',
              'group' => 3,
              'results' => [
                'throws' => [2, 2, 1, 4, 3, 3, 2, 3, 3, 2, 2, 3, 3, 4, 3, 4, 3, 3],
                'total' => 48,
                'to_par' => -6,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ], [
              'id' => 1,
              'name' => 'Marko Saviauk',
              'class' => 'mehed',
              'group' => 1,
              'results' => [
                'throws' => [3, 4, 5, 2, 3, 4, 5, 2, 2, 3, 5, 2, 4, 5, 5, 3, 3, 5],
                'total' => 65,
                'to_par' => 11,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ], [
              'id' => 5564,
              'name' => 'Anette Ojastu',
              'class' => 'naised',
              'group' => 14,
              'results' => [
                'throws' => [4, 4, 4, 4, 4, 3, 4, 4, 3, 7, 6, 3, 3, 4, 3, 4, 3, 3],
                'total' => 70,
                'to_par' => 16,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ], [
              'id' => 19443,
              'name' => 'Ivar Oja',
              'class' => 'mehed',
              'group' => 1,
              'results' => [
                'throws' => [5, 6, 5, 5, 5, 6, 5, 6, 3, 8, 3, 4, 5, 4, 4, 4, 4, 3],
                'total' => 85,
                'to_par' => 31,
                'ob' => [0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ]
          ]
        ];
      case 367747:
        return [
          'par_total' => 54,
          'holes' => [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
          'players' => [
            [
              'id' => 6216,
              'name' => 'Silver Saks',
              'class' => 'mehed',
              'group' => 3,
              'results' => [
                'throws' => [2, 2, 1, 4, 3, 3, 2, 3, 3, 2, 2, 3, 3, 4, 3, 4, 3, 3],
                'total' => 48,
                'to_par' => -6,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ], [
              'id' => 19443,
              'name' => 'Ivar Oja',
              'class' => 'mehed',
              'group' => 1,
              'results' => [
                'throws' => [5, 6, 5, 5, 5, 6, 5, 6, 3, 8, 3, 4, 5, 4, 4, 4, 4, 3],
                'total' => 85,
                'to_par' => 31,
                'ob' => [0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ]
          ]
        ];
      case 367749:
      default:
        return [
          'par_total' => 54,
          'holes' => [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3],
          'players' => [
            [
              'id' => 1,
              'name' => 'Marko Saviauk',
              'class' => 'mehed',
              'group' => 1,
              'results' => [
                'throws' => [3, 4, 5, 2, 3, 4, 5, 2, 2, 3, 5, 2, 4, 5, 5, 3, 3, 5],
                'total' => 65,
                'to_par' => 11,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ], [
              'id' => 5564,
              'name' => 'Anette Ojastu',
              'class' => 'naised',
              'group' => 14,
              'results' => [
                'throws' => [4, 4, 4, 4, 4, 3, 4, 4, 3, 7, 6, 3, 3, 4, 3, 4, 3, 3],
                'total' => 70,
                'to_par' => 16,
                'ob' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                'extra' => [
                  [
                    'name' => 'green_hit',
                    'type' => 'bool',
                    'holes' => [0, 1, 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0],
                    'total' => '44%'
                  ], [
                    'name' => 'outside_circle_putt',
                    'type' => 'bool',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'total' => 1
                  ], [
                    'name' => 'inside_circle_putts',
                    'type' => 'number',
                    'holes' => [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1],
                    'total' => 0
                  ], [
                    'name' => 'penalty',
                    'type' => 'number',
                    'holes' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
                    'total' => 1
                  ]
                ]
              ]
            ]
          ]
        ];
    }
  }

  public static function get_competition_filter($filters, $atts) {
    $selected = '';
    $options = self::get_competition_filter_option($filters['competition'], $atts, $selected);

    ob_start();
    ?>
    <div class="skoorin-results-filter-control-select-competition" data-name="competition">
      <div class="skoorin-select-competition">
        <div class="select">
          <div class="selected"><?php echo $selected ?></div>
          <div class="options">
            <ul class="list">
              <?php echo $options ?>
            </ul>
          </div>
        </div>
        <span class="button small">↑</span>
        <span class="button small">↓</span>
      </div>
    </div>
    <?php 
    return ob_get_clean();
  }

  static function get_competition_filter_option($competition, $atts, &$selected) {
    $is_selected = $competition['id'] == $atts['competition_id'];
    $class = 'competition';

    if ($is_selected) {
      $selected = $competition['name'];
      $class.= ' active';
    }

    $output = "<li><span class='$class' data-id='$competition[id]'>$competition[name]</span>";
      if (array_key_exists('children', $competition) && count($competition['children'])) {
        $output.= '<ul class="list">';
        foreach ($competition['children'] as $child_competition)
          $output.= self::get_competition_filter_option($child_competition, $atts, $selected);
        $output.= '</ul>';
      }
    $output.= '</li>';

    return $output;
  }

  public static function get_player_filter($filters, $atts, $l10n) {
    if (!count($filters['player']))
      return '';

    $output = "<div class='skoorin-results-filter-control-select-player' data-name='player'><select name='player'>";
    $output.= '<option value="all" '.($atts['player'] == 'all' ? 'selected' : '').'>'.$l10n['all']['player'].'</option>';

    foreach ($filters['player'] as $player)
      $output.= "<option value='$player[id]' ".($atts['player'] == $player['id'] ? 'selected' : '').">$player[name]</option>";

    $output.= '</select></div>';

    return $output;
  }

  public static function get_class_filter($filters, $atts, $l10n) {
    if (!count($filters['class']))
      return '';

    $output = "<div class='skoorin-results-filter-control-select-class' data-name='class'><select name='class'>";
    $output.= '<option value="all" '.($atts['class'] == 'all' ? 'selected' : '').'>'.$l10n['all']['class'].'</option>';

    foreach ($filters['class'] as $class)
      $output.= "<option value='$class[id]' ".($atts['class'] == $class['id'] ? 'selected' : '').">$class[name]</option>";

    $output.= '</select></div>';

    return $output;
  }

  public static function get_group_filter($filters, $atts, $l10n) {
    if (!count($filters['group']))
      return '';

    $output = "<div class='skoorin-results-filter-control-select-group' data-name='group'><select name='group'>";
    $output.= '<option value="all" '.($atts['group'] == 'all' ? 'selected' : '').'>'.$l10n['all']['group'].'</option>';

    foreach ($filters['group'] as $group)
      $output.= "<option value='$group[id]' ".($atts['group'] == $group['id'] ? 'selected' : '').">$group[name]</option>";

    $output.= '</select></div>';

    return $output;
  }

  public static function get_results_table($results, $l10n) {
    ob_start();
    ?>
      <table>
        <colgroup>
          <col width="0%">
          <col width="100%">
        </colgroup>
        <thead>
          <tr>
            <th class="hole" colspan="2"><?php echo $l10n['hole'] ?></th>
            <?php
              foreach ($results['holes'] as $idx => $par) {
                $num = $idx + 1;
                echo "<th>$num</th>";
              }
            ?>
            <th><?php echo $l10n['tot'] ?></th>
            <th><?php echo $l10n['to_par'] ?></th>
          </tr>
          <tr class="par">
            <th class="par" colspan="2"><?php echo $l10n['par'] ?></th>
            <?php
              foreach ($results['holes'] as $idx => $par)
                echo "<th>$par</th>";
            ?>
            <th><?php echo $results['par_total'] ?></th>
            <th></th>
          </tr>
        </thead>
        <?php foreach ($results['players'] as $idx => $player) { ?>
          <tbody>
            <tr>
              <td class="standing"><?php echo $idx + 1; ?></td>
              <td class="player">
                <a href="https://skoorin.com/?u=player_stat&player_user_id=<?php echo $player['id'] ?>" target="_blank">
                  <?php echo $player['name'] ?>
                </a>
              </td>
              <?php
                foreach ($player['results']['throws'] as $idx => $value) {
                  $class = self::get_score_class($idx, $results, $player);
                  echo "<td class='$class'>$value</td>";
                }
              ?>
              <td class="total"><?php echo $player['results']['total'] ?></td>
              <td class="balance"><?php echo ($player['results']['to_par'] >= 0 ? '+' : '').$player['results']['to_par'] ?></td>
            </tr>
          </tbody>
        <?php } ?>
      </table>
    <?php
    return ob_get_clean();
  }

  public static function get_score_class($idx, $results, $player) {
    $throws = $player['results']['throws'][$idx];
    $par = $results['holes'][$idx];
    $ob = array_key_exists('ob', $player['results']) && $player['results']['ob'][$idx] ? ' ob' : '';

    if ($throws == 1)
      return 'hole-in-one';

    switch ($throws - $par) {
      case -2:
        return 'eagle'.$ob;
      case -1:
        return 'birdie'.$ob;
      case 0:
        return 'par'.$ob;
      case 1:
        return 'bogey'.$ob;
      case 2:
        return 'double-bogey'.$ob;
      default:
        return 'fail'.$ob;
    }
  }
}

global $skoorin;
$skoorin = new Skoorin();

/**
 * Util.
 */
function skoorin_dump($var) {
  echo '<pre style="font-size: 12px; height: 400px; overflow: auto;">';
  print_r($var);
  echo '</pre>';
}