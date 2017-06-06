<?php
if (!defined('ABSPATH'))
  exit;

class Skoorin_Results_Table {
  function __construct($competition, $filters_state, $l10n, $profile_link_icon_path) {
    $this->competition = $competition;
    $this->filters_state = $filters_state;
    $this->l10n = $l10n;
    $this->sub_competition_date_fmt = 'm/d/y H:i';
    $this->no_class_flag = '$___NO_CLASS';
    $this->profile_link_fmt = 'https://dgmtrx.com/?u=player_stat&player_user_id=%s';
    $this->profile_link_icon_path = $profile_link_icon_path;
  }

  /**
   * Returns the results table markup
   */
  function get() {
    $has_subcompetitions = property_exists($this->competition, 'SubCompetitions')
      && is_array($this->competition->SubCompetitions)
      && count($this->competition->SubCompetitions);
    $show_extras = !$has_subcompetitions
      && property_exists($this->competition, 'MetrixMode')
      && (int) $this->competition->MetrixMode == 2;
    $show_previous_rounds_sum = (int) $this->competition->ShowPreviousRoundsSum;
    $aggregated_results = $this->aggregate_results();
    $players_by_classes = $this->get_players_by_classes($aggregated_results);
    $players_by_classes_filtered = $this->filter_players($players_by_classes);
    $num_competitions = is_array($this->competition->SubCompetitions) ? count($this->competition->SubCompetitions) : 0;
    if (is_array($this->competition->Results) && count($this->competition->Results))
      $num_competitions++;

    ob_start();
    ?>
      <table>
        <colgroup>
          <col width="0%">
          <col width="100%">
        </colgroup>
        <thead>
          <tr class="hole">
            <?php $colspan = $has_subcompetitions ? 3 : 2; ?>
            <th class="hole" colSpan="<?php echo $colspan ?>"></th>
            <?php
              foreach ($this->competition->Tracks as $track)
                echo "<th>$track->Number</th>";
            ?>
            <th><?php echo $this->l10n['tot'] ?></th>
            <th><?php echo $this->l10n['to_par'] ?></th>
            <?php
              if ($has_subcompetitions || $show_previous_rounds_sum)
                echo '<th></th><th></th>';
            ?>
          </tr>
          <tr class="par">
            <th class="par" colSpan="<?php echo $colspan ?>"><?php echo $this->l10n['par'] ?></th>
            <?php
              $par_sum = 0;
              foreach ($this->competition->Tracks as $track) {
                $par_sum += (int) $track->Par;
                echo "<th>$track->Par</th>";
              }
            ?>
            <th><?php echo $par_sum ?></th>
            <th></th>
            <?php
              if ($has_subcompetitions || $show_previous_rounds_sum)
                echo '<th></th><th></th>';
            ?>
          </tr>
        </thead>
        <?php
          $colspan = count($this->competition->Tracks) + ($has_subcompetitions ? 7 : $show_previous_rounds_sum ? 6 : 4);
          foreach ($players_by_classes_filtered as $class_name => $players) {
            if ($class_name != $this->no_class_flag && count($players)) { ?>
              <thead>
                <tr class="class">
                  <?php $num_players = count($players_by_classes[$class_name]); ?>
                  <th class="class" colSpan="<?php echo $colspan ?>"><?php echo "$class_name ($num_players)" ?></th>
                </tr>
              </thead>
            <?php }
            $standing = 0;
            $prev_total_diff = 0;
            foreach ($this->order_players_by_total_score($players, $num_competitions) as $name => $player) {
              $total_diff = array_sum($player->Diff);
              $total_sum = array_sum($player->Sum);
              if ($prev_total_diff != ($show_previous_rounds_sum ? $player->PreviousRoundsDiff : $total_diff))
                $standing++;
              $prev_total_diff = ($show_previous_rounds_sum ? $player->PreviousRoundsDiff : $total_diff);
              ?>
              <tbody>
                <?php
                  $num_result_rows = count($player->PlayerResults);
                  $result_row_num = 0;
                  foreach ($player->PlayerResults as $competition_key => $results) {
                    $result_row_num++; ?>
                    <tr>
                      <?php if ($result_row_num == 1) { ?>
                        <td class="standing" rowSpan="<?php echo $num_result_rows ?>"><?php echo $standing; ?></td>
                        <td class="player" rowSpan="<?php echo $num_result_rows ?>">
                          <?php if ($result_row_num == 1)
                            echo $show_extras
                              ? "<a class='expand-metrix' href='#'><i></i> $player->Name</a>"
                              : $player->Name;
                            if (property_exists($player, 'UserID')) {
                              $profile_url = sprintf($this->profile_link_fmt, $player->UserID);
                              echo "<a class='profile-link' target='_blank' href='$profile_url'><span><svg width='100%' height='100%' viewBox='0 0 512 512' preserveAspectRatio='xMidYMid meet'><path d='$this->profile_link_icon_path'/></svg></span></a>";
                            }
                          ?>
                        </td>
                      <?php }
                      if ($has_subcompetitions)
                        echo $this->get_subcompetition_cell($competition_key);
                      foreach ($results as $idx => $score)
                        echo $this->get_score_cell($idx, $score);
                      ?>
                      <td class="sum"><?php echo $player->Sum[$competition_key] ?></td>
                      <td class="diff"><?php echo $this->add_plus($player->Diff[$competition_key]) ?></td>
                      <?php if ($has_subcompetitions && $result_row_num == 1) { ?>
                        <td class="total-sum" rowSpan="<?php echo $num_result_rows ?>"><?php echo $total_sum ?></td>
                        <td class="total-diff" rowSpan="<?php echo $num_result_rows ?>"><?php echo $this->add_plus($total_diff) ?></td>
                      <?php } else if ($show_previous_rounds_sum) { ?>
                        <td class="total-sum" rowSpan="<?php echo $num_result_rows ?>"><?php echo $player->PreviousRoundsSum ?></td>
                        <td class="total-diff" rowSpan="<?php echo $num_result_rows ?>"><?php echo $this->add_plus($player->PreviousRoundsDiff) ?></td>
                      <?php } ?>
                    </tr>
                  <?php }
                ?>
              </tbody>
            <?php }
          } ?>
      </table>
    <?php
    return ob_get_clean();
  }

  function aggregate_results() {
    $players = array();
    $add = function ($competition) use (&$players, &$add) {
      $key = $competition->Date.'T'.$competition->Time;

      foreach ($competition->Results as $player) {
        if (!array_key_exists($player->Name, $players)) {
          $players[$player->Name] = clone $player;
          $players[$player->Name]->Sum = array();
          $players[$player->Name]->Diff = array();
          $players[$player->Name]->PlayerResults = array();
        }
        $players[$player->Name]->Sum[$key] = $player->Sum;
        $players[$player->Name]->Diff[$key] = $player->Diff;

        if (is_array($player->PlayerResults))
          foreach ($player->PlayerResults as $score)
            $players[$player->Name]->PlayerResults[$key][] = $score;
      }

      if (
        property_exists($competition, 'SubCompetitions') &&
        is_array($competition->SubCompetitions) &&
        count($competition->SubCompetitions)
      )
        foreach ($competition->SubCompetitions as $sub_competition)
          $add($sub_competition);
    };
    
    $add($this->competition);

    return $players;
  }

  function get_players_by_classes($players) {
    $players_by_classes = array();

    foreach ($players as $id => $player) {
      $class_name = property_exists($player, 'ClassName') && !empty($player->ClassName)
        ? $player->ClassName
        : $this->no_class_flag;
      $players_by_classes[$class_name][$id] = $player;
    }

    return $players_by_classes;
  }

  function filter_players($players_by_classes) {
    $filter_map = array(
      'players' => 'Name',
      'classes' => 'ClassName',
      'groups' => 'Group'
    );
    $filtered = $players_by_classes;

    foreach ($this->filters_state as $filter_name => $filter_value)
      if ($filter_value != 'all') {
        foreach ($filtered as $class_name => $players) {
          if ($filter_name == 'classes') {
            if ($filter_value != $class_name)
              $filtered[$class_name] = array();
          } else
            $filtered[$class_name] = array_filter($players, function ($player) use ($filter_name, $filter_value, $filter_map) {
              return $filter_name != 'players'
                ? $player->{$filter_map[$filter_name]} == $filter_value
                : is_array($filter_value) && in_array($player->Name, $filter_value);
            });
        }
        break; // only one filter may be active at a time
      }

    return $filtered;
  }

  function order_players_by_total_score($players, $num_competitions) {
    $missing_competitions = function ($player) use ($num_competitions) {
      return (
        !is_array($player->PlayerResults) ||
        count($player->PlayerResults) < $num_competitions
      );
    };
    $missing_results = function ($player) {
      foreach ($player->PlayerResults as $key => $results)
        foreach ($results as $idx => $score)
          if (!is_object($score) || !property_exists($score, 'Result'))
            return true;
      
      return false;
    };
    $get_total = function($diff) {
      return is_array($diff) ? array_sum($diff) : $diff;
    };
    $prop = property_exists($this->competition, 'ShowPreviousRoundsSum') && (int) $this->competition->ShowPreviousRoundsSum
      ? 'PreviousRoundsDiff'
      : 'Diff';
    $orig_order = array_keys($players);

    usort($players, function ($a, $b) use ($missing_results, $missing_competitions, $orig_order, $get_total, $prop) {
      if (!$missing_results($a) && $missing_results($b))
        return -1;
      if ($missing_results($a) && !$missing_results($b))
        return 1;
      if (!$missing_competitions($a) && $missing_competitions($b))
        return -1;
      if ($missing_competitions($a) && !$missing_competitions($b))
        return 1;

      if ($get_total($a->{$prop}) < $get_total($b->{$prop}))
        return -1;
      if ($get_total($a->{$prop}) > $get_total($b->{$prop}))
        return 1;

      if (array_search($a, $orig_order) < array_search($b, $orig_order))
        return -1;
      if (array_search($a, $orig_order) > array_search($b, $orig_order))
        return 1;

      return 0;
    });

    return $players;
  }

  function get_subcompetition_cell($competition_key) {
    return '<td>'.date($this->sub_competition_date_fmt, strtotime($competition_key)).'</td>';
  }

  function get_score_cell($idx, $score) {
    $score = (object) $score;
    $score_class = $this->get_score_class($score);
    $track_num = property_exists($this->competition, 'Tracks')
      && is_array($this->competition->Tracks)
      && array_key_exists($idx, $this->competition)
      && is_object($this->competition->Tracks[$idx])
      && property_exists($this->competition->Tracks[$idx], 'Number')
        ? $this->competition->Tracks[$idx]->Number
        : $idx + 1;
    $title_att = sprintf(
      $this->l10n['score_tooltip'],
      $track_num,
      property_exists($score, 'Diff') ? $score->Diff : '-',
      $score_class['title'],
      $score_class['is_ob']
        ? $this->l10n['score_tooltip_ob']
        : ''
    );
    $res = property_exists($score, 'Result')
      ? $score->Result
      : '';
    return "<td class='$score_class[class]' title='$title_att'>$res</td>";
  }

  function get_score_class($score) {
    $is_ob = property_exists($score, 'OB') ? (int) $score->OB : 0;
    $ob_class = $is_ob ? ' ob' : '';

    if (!property_exists($score, 'Result'))
      $class = 'no-score';
    else {
      $diff = (int) $score->Diff;

      if ((int) $score->Result == 1)
        $class = 'hole-in-one';
      else
        switch ($diff) {
          case -2:
            $class = 'eagle';
            break;
          case -1:
            $class = 'birdie';
            break;
          case 0:
            $class = 'par';
            break;
          case 1:
            $class = 'bogey';
            break;
          case 2:
            $class = 'double-bogey';
            break;
          default:
            $class = $diff < -2 ? 'eagle' : 'fail';
        }
    }
    
    return array(
      'title' => $this->l10n['score_terms'][$class],
      'is_ob' => $is_ob,
      'class' => $class.$ob_class
    );
  }

  function add_plus($num) {
    return ((float) $num > 0 ? '+' : '').$num;
  }
}
