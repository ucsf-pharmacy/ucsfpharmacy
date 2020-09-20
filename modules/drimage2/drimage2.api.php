<?php

/**
 * @file
 * Hooks and documentation related to drimage module.
 */

/**
 * Alter the possible proxy cache periods.
 *
 * @param array $periods
 *   The array of proxy cache periods.
 */
function hook_drimage2_proxy_cache_periods_alter(array &$periods) {
  // Set a new proxy cache period
  $periods[] = 32400;
}

