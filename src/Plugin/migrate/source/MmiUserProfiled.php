<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * MMI D7 users that carry at least one profile2 row.
 *
 * The base row set for mmi_profiles: one osu_profile node per profiled
 * person, whether or not their account is in mmi_users scope (a profile is
 * public content in its own right; node ownership falls back to uid 1 for
 * the unmigrated accounts, mirroring agsci's upgrade_d7_user_to_profile).
 *
 * @MigrateSource(
 *   id = "mmi_d7_user_profiled",
 *   source_module = "user"
 * )
 */
class MmiUserProfiled extends User {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->exists(
      $this->select('profile', 'p')
        ->fields('p', ['pid'])
        ->where('p.uid = u.uid')
    );
    return $query;
  }

}
