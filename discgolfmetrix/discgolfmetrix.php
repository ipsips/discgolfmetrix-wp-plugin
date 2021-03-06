<?php
/*
Plugin Name:  Disc Golf Metrix
Plugin URI:   http://discgolfmetrix.com
Description:  Embed discgolfmetrix.com content into wordpress. Requires PHP ver 5.4 or higher
Version:      2.0.0
Author:       Discgolfmetrix
Author URI:   http://discgolfmetrix.com
License:      Apache License 2.0
License URI:  https://www.apache.org/licenses/LICENSE-2.0.txt
Text Domain:  discgolfmetrix
Domain Path:  /languages
*/

if (!defined('ABSPATH'))
  exit;

require_once 'discgolfmetrix-l10n.php';
require_once 'discgolfmetrix-settings.php';
require_once 'discgolfmetrix-api.php';
require_once 'discgolfmetrix-results-table.php';

class DiscGolfMetrix {
  function __construct($l10n) {
    $this->ver = '2.0.0';
    $this->options = get_option('discgolfmetrix_options', DiscGolfMetrix_Settings::get_default_options());
    $this->defaults = array(
      'shortcode_results' => array(
        'competition_id' => 0,
        'players' => 'all',
        'class' => 'all',
        'group' => 'all'
      )
    );
    $this->sub_competition_date_fmt = 'm/d/y H:i';
    $this->no_class_flag = '$___NO_CLASS';
    $this->profile_link_icon_path = 'M340.254,214.706c14.96-14.142,13.867-45.041,1.742-55.538 c9.644-25.638,22.011-55.482,16.79-81.621c-6.405-32.045-51.91-43.413-88.934-43.413c-28.713,0-63.591,7.238-74.587,27.047 c-11.052,1.222-19.587,5.794-25.419,13.645c-16.068,21.695-5.092,55,2.815,83.991c-12.127,10.514-13.571,41.747,1.388,55.889 c2.055,29.399,19.438,47.412,27.565,54.316v44.579c-7.793,2.906-15.422,5.72-22.844,8.442 c-64.257,23.621-110.687,40.709-124.276,67.905C35.222,428.471,35,467.534,35,469.18c0,5.109,4.148,9.257,9.257,9.257h425.788 c5.109,0,9.257-4.148,9.257-9.257c0-1.646-0.222-40.709-19.494-79.232c-13.607-27.214-60.036-44.284-124.294-67.905 c-7.404-2.739-15.051-5.536-22.826-8.442v-44.579C320.815,262.099,338.199,244.105,340.254,214.706L340.254,214.706z';
    $this->l10n = $l10n['results'];

    add_shortcode('discgolfmetrix_results', array($this, 'shortcode_results'));
    add_shortcode('discgolfmetrix_registration_list', array($this, 'shortcode_registration_list'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_public'), 99);
  }

  function enqueue_scripts_public() {
    if ($this->is_shortcode_in_post_content('discgolfmetrix_results')) {
      wp_enqueue_style('discgolfmetrix-results', plugins_url('styles/discgolfmetrix-results.css', __FILE__), array(), $this->ver);
      wp_enqueue_script('discgolfmetrix-results', plugins_url('scripts/discgolfmetrix-results.js', __FILE__), array('jquery'), $this->ver, true);
      wp_localize_script('discgolfmetrix-results', 'discgolfmetrixResults', array(
        'l10n' => $this->l10n,
        'profile_link_icon_path' => $this->profile_link_icon_path,
        'ajax_url' => admin_url('admin-ajax.php')
      ));
    }
    if ($this->is_shortcode_in_post_content('discgolfmetrix_registration_list'))
      wp_enqueue_style('discgolfmetrix-registration-list', plugins_url('styles/discgolfmetrix-registration-list.css', __FILE__), array(), $this->ver);
  }

  function is_shortcode_in_post_content($shortcode_key) {
    global $post;
    $pattern = get_shortcode_regex();

    return (
      is_object($post) &&
      property_exists($post, 'post_content') &&
      preg_match_all("/$pattern/s", $post->post_content, $matches) &&
      array_key_exists(2, $matches) &&
      in_array($shortcode_key, $matches[2])
    );
  }
  
  function shortcode_results($attributes) {
    $atts = shortcode_atts($this->defaults['shortcode_results'], $attributes);

    if (!is_numeric($atts['competition_id']))
      return '';
    
    extract($this->options);
    $results_filter = json_decode($results_filter);
    $filters = DiscGolfMetrix_API::get(array(
      'content' => 'wordpress_filters',
      'competition_id' => $atts['competition_id']
    ));
    $results = DiscGolfMetrix_API::get(array(
      'content' => 'result',
      'id' => $atts['competition_id']
    ));
    $is_api_query_error = function ($response) {
      return empty($response) || (is_array($response) && array_key_exists('error', $response));
    };

    $output = "<div class='discgolfmetrix-results' data-competition-id='$atts[competition_id]'>";

      if ($is_api_query_error($filters) || $is_api_query_error($results))
        return "$output<p class='error'>{$this->l10n['network_error']}</p></div>";

      $filters_state = $this->get_filters_state($atts, $results_filter, $filters);

      /* filter */
      if (is_array($results_filter) && count($results_filter)) {
        $active_filter_found = false;
        $output .= '<div class="discgolfmetrix-results-filter">';
        foreach ($results_filter as $filter_name) {
          $output .= call_user_func(
            get_class()."::get_{$filter_name}_filter",
            $filters,
            $atts,
            $this->l10n,
            $filter_name == 'competitions' || $active_filter_found
              ? null
              : $filters_state[$filter_name]
          );
          if (array_key_exists($filter_name, $filters_state) && $filters_state[$filter_name] != 'all')
            $active_filter_found = true;
        }
        $output .= '</div>';
      }

      /* results table */
      if (property_exists($results, 'Competition')) {
        $competition = $results->Competition;
        $output .= "<div class='discgolfmetrix-results-table'><div class='discgolfmetrix-results-table-container'>";
        $results_table = new DiscGolfMetrix_Results_Table(
          $competition,
          $filters_state,
          $this->l10n,
          $this->profile_link_icon_path
        );
        $output .= $results_table->get();
        $output .= '</div></div>';
      }

      /* data for js */
      $output .= '<script type="application/json" class="discgolfmetrix-results-data">';
      $output .= json_encode(array(
        'filters_selected' => $results_filter,
        'filters' => $filters,
        'results' => $results
      ));
      $output .= '</script>';
    
    $output .= '</div>';

    return $output;
  }

  function shortcode_registration_list($attributes) {
    $atts = shortcode_atts($this->defaults['shortcode_results'], $attributes);

    if (!is_numeric($atts['competition_id']))
      return '';
    
    $registration_list = DiscGolfMetrix_API::get(array(
      'content' => 'registration_list_html',
      'id' => $atts['competition_id']
    ), 'html');
    $is_api_query_error = function ($response) {
      return is_array($response) && array_key_exists('error', $response);
    };

    $output = "<div class='discgolfmetrix-registration-list' data-competition-id='$atts[competition_id]'>";
      $output .= $is_api_query_error($registration_list)
        ? "$output<p class='error'>{$this->l10n['network_error']}</p>"
        : $registration_list;
    $output .= '</div>';

    return $output;
  }

  function get_filters_state($atts, $filters_selected, $filters_data) {
    $singular_filter_names = array(
      'classes' => 'class',
      'groups' => 'group'
    );
    $filters_state = array();
    $in_available_options = function ($selected_option, $filter_name) use ($filters_data) {
      if (
        !property_exists($filters_data, $filter_name) ||
        !is_array($filters_data->{$filter_name}) ||
        !count($filters_data->{$filter_name})
      )
        return false;

      switch ($filter_name) {
        case 'groups':
          return array_filter($filters_data->{$filter_name}, function ($available_group) use ($selected_option) {
            return $available_group->Number == $selected_option;
          });
        default:
          return in_array(
            (object) array('Name' => $selected_option),
            $filters_data->{$filter_name}
          );
      }
    };

    foreach ($filters_selected as $filter_name)
      if ($filter_name != 'competitions') {
        switch ($filter_name) {
          case 'competitions':
            break;
          case 'players':
            $selected = array();
            if (array_key_exists($filter_name, $atts)) {
              $selected = array_map('trim', explode(',', $atts['players']));

              if (count($selected))
                $selected = in_array('all', $selected)
                  ? []
                  : array_filter($selected, function ($selected_option) use ($in_available_options) {
                      return $in_available_options($selected_option, 'players');
                    });
            }
            $filters_state['players'] = count($selected)
              ? $selected
              : 'all';
            break;
          default:
            $singular_filter_name = array_key_exists($filter_name, $singular_filter_names)
              ? $singular_filter_names[$filter_name]
              : preg_replace('/s$/', '', $filter_name);

            $filters_state[$filter_name] = !array_key_exists($singular_filter_name, $atts) ||
              empty($atts[$singular_filter_name]) ||
              !$in_available_options($atts[$singular_filter_name], $filter_name)
                ? 'all'
                : $atts[$singular_filter_name];
            break;
        }
      }

    return $filters_state;
  }

  public static function get_competitions_filter($filters, $atts, $l10n) {
    $selected = '';
    $options = self::get_competitions_filter_option($filters->competitions, $atts, $selected);

    ob_start();
    ?>
      <div class="discgolfmetrix-results-filter-control-select-competitions" data-name="competitions">
        <div class="discgolfmetrix-select-competitions">
          <div class="select">
            <div class="selected">
              <select>
                <option><?php echo $selected ?></option>
              </select>
            </div>
            <div class="options">
              <ul class="list">
                <?php echo $options ?>
              </ul>
            </div>
          </div>
          <span class="competition-nav-button small">↑</span>
          <span class="competition-nav-button small">↓</span>
        </div>
      </div>
    <?php 
    return ob_get_clean();
  }

  static function get_competitions_filter_option($competition, $atts, &$selected) {
    $is_selected = $competition->ID == $atts['competition_id'];
    $class = 'competition';

    if ($is_selected) {
      $selected = $competition->Name;
      $class .= ' active';
    }

    ob_start();
    ?>
      <li><span class="<?php echo $class ?>" data-id="<?php echo $competition->ID ?>"><?php echo $competition->Name ?></span>
        <?php
          if (property_exists($competition, 'SubCompetitions') && count($competition->SubCompetitions)) {
            echo '<ul class="list">';
              foreach ($competition->SubCompetitions as $child_competition)
                echo self::get_competitions_filter_option($child_competition, $atts, $selected);
            echo '</ul>';
          }
        ?>
      </li>
    <?php
    return ob_get_clean();
  }

  public static function get_players_filter($filters, $atts, $l10n, $selected = null) {
    $got_data = property_exists($filters, 'players') && count($filters->players);
    $is_any_selected = is_array($selected) && count($selected);

    ob_start();
    ?>
      <div class="discgolfmetrix-results-filter-control-select-players <?php echo $got_data ? '' : 'no-options' ?>" data-name="players">
        <select class="placeholder visible">
          <option>
            <?php echo !$is_any_selected
              ? $l10n['all']['players']
              : (count($selected) == 1
                ? $selected[0]
                : $l10n['multiple']['players'])
            ?>
          </option>
        </select>
        <select name="players" multiple autoComplete="off">
          <option value="all" <?php if (!$is_any_selected) echo 'selected'; ?>><?php echo $l10n['all']['players'] ?></option>
          <?php
            if ($got_data)
              foreach ($filters->players as $player)
                echo "<option value='$player->Name' ".($is_any_selected && in_array($player->Name, $selected) ? 'selected' : '').">$player->Name</option>";
          ?>
        </select>
      </div>
    <?php
    return ob_get_clean();
  }

  public static function get_classes_filter($filters, $atts, $l10n, $selected = null) {
    $got_data = property_exists($filters, 'classes') && count($filters->classes);

    ob_start();
    ?>
      <div class="discgolfmetrix-results-filter-control-select-classes <?php echo $got_data ? '' : 'no-options' ?>" data-name="classes">
        <select name="classes">
          <option value="all" <?php if (!$selected) echo 'selected'; ?>><?php echo $l10n['all']['classes'] ?></option>
          <?php
            if ($got_data)
              foreach ($filters->classes as $class)
                echo "<option value='$class->Name' ".($selected == $class->Name ? 'selected' : '').">$class->Name</option>";
          ?>
        </select>
      </div>
    <?php
    return ob_get_clean();
  }

  public static function get_groups_filter($filters, $atts, $l10n, $selected = null) {
    $got_data = property_exists($filters, 'groups') && count($filters->groups);

    ob_start();
    ?>
      <div class="discgolfmetrix-results-filter-control-select-groups <?php echo $got_data ? '' : 'no-options' ?>" data-name="groups">
        <select name="groups">
          <option value="all" <?php if (!$selected) echo 'selected'; ?>><?php echo $l10n['all']['groups'] ?></option>
          <?php
            if ($got_data)
              foreach ($filters->groups as $group) {
                $label = $group->Number.(
                  property_exists($group, 'Time') && !empty($group->Time)
                    ? " ($group->Time)"
                    : ''
                );
                echo "<option value='$group->Number' ".($selected == $group->Number ? 'selected' : '').">$label</option>";
              }
          ?>
        </select>
      </div>
    <?php
    return ob_get_clean();
  }
}

global $discgolfmetrix;
$discgolfmetrix = new DiscGolfMetrix($discgolfmetrix_l10n);
