<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * MMI D7 users that the migrated content actually needs.
 *
 * Restricts the core d7_user source to accounts referenced somewhere: node
 * author, revision author, OG group member or webform submitter. The other
 * accounts (22 at audit time) are dormant, match nothing on the live site and
 * would only be clutter; if a later refresh makes one referenced, this query
 * picks it up on the next run.
 *
 * The 16 accounts that map to an existing D10 person by ONID are still
 * selected here, but scripts-dev/mmi_preseed_user_map.php has already written
 * their map rows (ROLLBACK_PRESERVE, pointing at the live uid), so migrate
 * skips them as imported and rollback leaves the live accounts alone.
 *
 * @MigrateSource(
 *   id = "mmi_d7_user_referenced",
 *   source_module = "user"
 * )
 */
class MmiUserReferenced extends User {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $referenced = $query->orConditionGroup()
      ->exists($this->select('node', 'n')->fields('n', ['nid'])->where('n.uid = u.uid'))
      ->exists($this->select('node_revision', 'nr')->fields('nr', ['vid'])->where('nr.uid = u.uid'))
      ->exists($this->select('og_membership', 'og')->fields('og', ['id'])->where("og.entity_type = 'user' AND og.etid = u.uid"))
      ->exists($this->select('webform_submissions', 'ws')->fields('ws', ['sid'])->where('ws.uid = u.uid'));
    $query->condition($referenced);
    return $query;
  }

}
