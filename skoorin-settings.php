<?php

if (!defined('ABSPATH'))
  exit;

class Skoorin_Settings {
  function __construct() {
    $this->options = get_option('skoorin_options', self::get_default_options());
    $this->title = __('Skoorin', 'skoorin');

    register_uninstall_hook(__FILE__, array('Skoorin_Settings', 'on_uninstall'));
    add_action('admin_menu', array($this, 'add_admin_menu_item'), 99);
    add_action('admin_init', array($this, 'register_settings'));
    add_filter('plugin_action_links_skoorin/skoorin.php', array($this, 'add_plugin_action_links'));
  }

  static function on_uninstall() {
    delete_option('skoorin_options');
    delete_site_option('skoorin_options'); // multisite
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
      $this->title,                       // page title in <head>
      $this->title,                       // menu title
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
      'skoorin_options_section',
      '',
      array($this, 'render_section'),
      'skoorin_options_screen'
    );
  }

  function render_options_page() {
    ?>
      <div id="skoorin" class="wrap wrap acf-settings-wrap">
        <h2><?php echo $this->title ?></h2>
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
  
  function render_section() {
    $results_filter = $this->options['results_filter'];

    echo '<p>TODO: Drag & drop interface to choose filter controls:</p>';
    echo "<pre>";
    print_r($results_filter);
    echo "</pre>";
  }
  
  function sanitize($input) {
    $output = array();
    $option_names = array_keys(self::get_default_options());
    
    /* Save only the options that are defined in default options */
    foreach($option_names as $n)
      if (isset($input[$n]))
        $output[$n] = $input[$n];
    
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
      'results_filter' => array(
        'select_competition',
        'select_player',
        'select_class',
        'select_group'
      ),
      'responsive_table' => true
    );
  }
}
new Skoorin_Settings();