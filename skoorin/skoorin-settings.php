<?php

if (!defined('ABSPATH'))
  exit;

require_once 'skoorin-l10n.php';

class Skoorin_Settings {
  function __construct($l10n) {
    $this->ver = '2.0.0';
    $this->options = get_option('skoorin_options', self::get_default_options());
    $this->l10n = $l10n;

    register_uninstall_hook(__FILE__, array('Skoorin_Settings', 'on_uninstall'));
    add_action('admin_menu', array($this, 'add_admin_menu_item'), 99);
    add_action('admin_init', array($this, 'register_settings'));
    add_filter('plugin_action_links_skoorin/skoorin.php', array($this, 'add_plugin_action_links'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_admin'));
  }

  static function on_uninstall() {
    delete_option('skoorin_options');
    delete_site_option('skoorin_options'); // multisite
  }

  function enqueue_scripts_admin() {
    wp_enqueue_style('skoorin-settings', plugins_url('styles/skoorin-settings.css', __FILE__), array(), $this->ver);
    wp_enqueue_script('skoorin-settings', plugins_url('scripts/skoorin-settings.js', __FILE__), array('jquery', 'jquery-ui-sortable'), $this->ver, true);
  }

  function add_plugin_action_links($actions) {
    return array_merge([
      'settings' => sprintf(
        '<a href="%soptions-general.php?page=skoorin_options">%s</a>',
        admin_url(),
        __('Settings', 'skoorin')
      )
    ], $actions);
  }

  function add_admin_menu_item() {
    add_options_page(
      $this->l10n['settings']['title'], // page title in <head>
      $this->l10n['settings']['title'], // menu title
      'manage_options',                   // capability
      'skoorin_options',                  // menu slug
      array($this, 'render_options_page') // render function
    );
  }
  
  function register_settings() {
    register_setting(
      'skoorin_options_group',
      'skoorin_options',
      array($this, 'sanitize')
    );
    add_settings_section(
      'skoorin_options_results_filter_section',
      '',
      array($this, 'render_results_filter_section'),
      'skoorin_options_screen'
    );
  }

  function render_options_page() {
    ?>
      <div id="skoorin" class="wrap wrap acf-settings-wrap">
        <h2><?php echo $this->l10n['settings']['title'] ?></h2>
        <div id="skoorin-options">
          <form method="post" action="options.php" id="skoorin-options-form">
            <?php
              /* This prints out all hidden setting fields */
              settings_fields('skoorin_options_group');
              do_settings_sections('skoorin_options_screen');
              submit_button();
            ?>
          </form>
        </div>
      </div>
    <?php
  }
  
  function render_results_filter_section() {
    global $skoorin;

    $atts = $skoorin->defaults['shortcode_results'];
    $results_filter = json_decode($this->options['results_filter']);

    $filters_available = array(
      'competitions' => array('ID' => 0, 'Name' => $this->l10n['settings']['competitions']),
      'players' => array(array('Name' => $this->l10n['results']['all']['players'])),
      'classes' => array(array('Name' => $this->l10n['results']['all']['classes'])),
      'groups' => array(array('Number' => $this->l10n['results']['all']['groups']))
    );
    $filters_selected = array();

    if (is_array($results_filter) && count($results_filter))
      foreach ($results_filter as $name) {
        $filters_selected[$name] = $filters_available[$name];
        unset($filters_available[$name]);
      }

    echo '<div id="skoorin-filters">';
    echo "<p>{$this->l10n['settings']['filters_instructions']}</p>";
    echo "<h4>{$this->l10n['settings']['inactive_filters']}</h4>";
    echo '<div class="skoorin-results-filter filters-available">';
      foreach ($filters_available as $name => $options)
        echo call_user_func(
          "Skoorin::get_{$name}_filter",
          json_decode(json_encode($filters_available)),
          $atts,
          $this->l10n['results']
        );
    echo '</div>';
    echo "<h4>{$this->l10n['settings']['active_filters']}</h4>";
    echo '<div class="skoorin-results-filter filters-selected">';
      foreach ($filters_selected as $name => $options)
        echo call_user_func(
          "Skoorin::get_{$name}_filter",
          json_decode(json_encode($filters_selected)),
          $atts,
          $this->l10n['results']
        );
    echo '</div>';
    echo '<input type="hidden" name="skoorin_options[results_filter]" value='.json_encode(array_keys($filters_selected)).'>';
    echo '</div>';
  }
  
  function sanitize($input) {
    $output = array();
    $option_names = array_keys(self::get_default_options());
    
    /* Save only the options that are defined in default options */
    foreach($option_names as $name)
      if (isset($input[$name]))
        $output[$name] = $input[$name];

    return $output;
  }

  function _print_html_element_atts($atts) {
    foreach ($atts as $name => $value)
      if (!empty($value)) {
        $value = esc_attr($value);
        echo " $name=\"$value\"";
      }
  }

  public static function get_default_options() {
    return array(
      'results_filter' => '["competitions","players","classes","groups"]'
    );
  }
}

new Skoorin_Settings($skoorin_l10n);
