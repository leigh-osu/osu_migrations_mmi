<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Offsets an MMI D7 entity id into the reserved MMI id range.
 *
 * The CAS migration preserved agsci D7 nids verbatim, so the D10 node table
 * already owns the low id space (live max 302,221) and 116 MMI nids collide
 * with existing nodes. MMI node ids are therefore offset by a fixed
 * +400,000: MMI D7 node 4876 becomes D10 node 404876, readable in both
 * directions with no drift risk.
 *
 * The same offset must be applied everywhere a nid is encoded as text rather
 * than looked up: the mmi url_alias, path_redirect and menu_links migrations
 * rewrite node/<N> to node/<N + OFFSET> with this class's OFFSET constant as
 * the single source of truth. vids get the same offset since D7 vids share
 * the collision problem.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_nid_offset"
 * )
 */
class MmiNidOffset extends ProcessPluginBase {

  /**
   * The fixed id offset for all MMI node ids and revision ids.
   */
  public const OFFSET = 400000;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value === NULL || $value === '') {
      return NULL;
    }
    if (!is_numeric($value)) {
      throw new MigrateException(sprintf('mmi_nid_offset expects a numeric id, got "%s" for %s.', $value, $destination_property));
    }
    return (int) $value + self::OFFSET;
  }

}
