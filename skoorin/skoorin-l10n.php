<?php

$skoorin_l10n = array(
  'results' => array(
    'all' => array(
      'players' => __('All players', 'skoorin'),
      'classes' => __('All classes', 'skoorin'),
      'groups' => __('All groups', 'skoorin')
    ),
    'multiple' => array(
      'players' => __('Multiple players', 'skoorin')
    ),
    'hole' => __('Hole', 'skoorin'),
    'par' => __('Par', 'skoorin'),
    'sum' => __('Sum', 'skoorin'),
    'total' => __('Total', 'skoorin'),
    'to_par' => __('+/-', 'skoorin'),
    'extra' => array(
      'BUE' => __('Bullseye hit', 'skoorin'),
      'GRH' => __('Green hit', 'skoorin'),
      'OCP' => __('Outside circle putt', 'skoorin'),
      'ICP' => __('Inside circle putts', 'skoorin'),
      'IBP' => __('Inside bullseye putt', 'skoorin'),
      'PEN' => __('Penalty', 'skoorin')
    ),
    'score_tooltip' => __('Hole: %s, &#013;Diff: %s (%s)%s', 'skoorin'),
    'score_tooltip_ob' => __(', &#013;OB', 'skoorin'),
    'score_terms' => array(
      'hole-in-one' => __('Hole-in-one', 'skoorin'),
      'eagle' => __('Eagle', 'skoorin'),
      'birdie' => __('Birdie', 'skoorin'),
      'par' => __('Par', 'skoorin'),
      'bogey' => __('Bogey', 'skoorin'),
      'double-bogey' => __('Double-Bogey', 'skoorin'),
      'fail' => __('Fail', 'skoorin'),
      'ob' => __('OB', 'skoorin'),
      'no-score' => __('-', 'skoorin')
    ),
    'network_error' => __('There was a problem getting data from Skoorin data API!', 'skoorin'),
    'missing_data_error' => __('Missing data in API response', 'skoorin')
  ),
  'settings' => array(
    'title' => __('Skoorin', 'skoorin'),
    'competitions' => __('Competition', 'skoorin'),
    'inactive_filters' => __('Inactive filters', 'skoorin'),
    'active_filters' => __('Active filters', 'skoorin'),
    'filters_instructions' => __('Choose which filter controls you wish to display by dragging them from ‘inactive’ to ‘active’', 'skoorin')
  )
);
