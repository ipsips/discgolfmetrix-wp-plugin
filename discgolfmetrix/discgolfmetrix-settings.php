<?php

if (!defined('ABSPATH'))
  exit;

require_once 'discgolfmetrix-l10n.php';

class DiscGolfMetrix_Settings {
  function __construct($l10n) {
    $this->ver = '2.0.0';
    $this->options = get_option('discgolfmetrix_options', self::get_default_options());
    $this->l10n = $l10n;

    register_uninstall_hook(__FILE__, array('DiscGolfMetrix_Settings', 'on_uninstall'));
    add_action('admin_menu', array($this, 'add_admin_menu_item'), 99);
    add_action('admin_init', array($this, 'register_settings'));
    add_filter('plugin_action_links_discgolfmetrix/discgolfmetrix.php', array($this, 'add_plugin_action_links'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_admin'));
  }

  static function on_uninstall() {
    delete_option('discgolfmetrix_options');
    delete_site_option('discgolfmetrix_options'); // multisite
  }

  function enqueue_scripts_admin() {
    wp_enqueue_style('discgolfmetrix-settings', plugins_url('styles/discgolfmetrix-settings.css', __FILE__), array(), $this->ver);
    wp_enqueue_script('discgolfmetrix-settings', plugins_url('scripts/discgolfmetrix-settings.js', __FILE__), array('jquery', 'jquery-ui-sortable'), $this->ver, true);
  }

  function add_plugin_action_links($actions) {
    return array_merge([
      'settings' => sprintf(
        '<a href="%soptions-general.php?page=discgolfmetrix_options">%s</a>',
        admin_url(),
        __('Settings', 'discgolfmetrix')
      )
    ], $actions);
  }

  function add_admin_menu_item() {
    add_options_page(
      $this->l10n['settings']['title'], // page title in <head>
      $this->l10n['settings']['title'], // menu title
      'manage_options',                   // capability
      'discgolfmetrix_options',                  // menu slug
      array($this, 'render_options_page') // render function
    );
  }
  
  function register_settings() {
    register_setting(
      'discgolfmetrix_options_group',
      'discgolfmetrix_options',
      array($this, 'sanitize')
    );
    add_settings_section(
      'discgolfmetrix_options_results_filter_section',
      '',
      array($this, 'render_results_filter_section'),
      'discgolfmetrix_options_screen'
    );
  }

  function render_options_page() {
    ?>
      <div id="discgolfmetrix" class="wrap wrap acf-settings-wrap">
        <h2><?php echo $this->l10n['settings']['title'] ?></h2>
        <div id="discgolfmetrix-options">
          <form method="post" action="options.php" id="discgolfmetrix-options-form">
            <?php
              /* This prints out all hidden setting fields */
              settings_fields('discgolfmetrix_options_group');
              do_settings_sections('discgolfmetrix_options_screen');
              submit_button();
            ?>
          </form>
        </div>
      </div>
    <?php
  }
  
  function render_results_filter_section() {
    global $discgolfmetrix;

    $atts = $discgolfmetrix->defaults['shortcode_results'];
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

    echo '<div id="discgolfmetrix-filters">';
    echo "<p>{$this->l10n['settings']['filters_instructions']}</p>";
    echo "<h4>{$this->l10n['settings']['inactive_filters']}</h4>";
    echo '<div class="discgolfmetrix-results-filter filters-available">';
      foreach ($filters_available as $name => $options)
        echo call_user_func(
          "DiscGolfMetrix::get_{$name}_filter",
          json_decode(json_encode($filters_available)),
          $atts,
          $this->l10n['results']
        );
    echo '</div>';
    echo "<h4>{$this->l10n['settings']['active_filters']}</h4>";
    echo '<div class="discgolfmetrix-results-filter filters-selected">';
      foreach ($filters_selected as $name => $options)
        echo call_user_func(
          "DiscGolfMetrix::get_{$name}_filter",
          json_decode(json_encode($filters_selected)),
          $atts,
          $this->l10n['results']
        );
    echo '</div>';
    echo '<input type="hidden" name="discgolfmetrix_options[results_filter]" value='.json_encode(array_keys($filters_selected)).'>';
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

new DiscGolfMetrix_Settings($discgolfmetrix_l10n);
