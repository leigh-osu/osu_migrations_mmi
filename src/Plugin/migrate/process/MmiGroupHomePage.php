<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Resolves an MMI D7 group nid to its public home page node.
 *
 * D7 group nodes were dashboards ("It is not publicly visible..."); each
 * lab's public home is an ordinary book node. On D10 the group's canonical
 * URL redirects anonymous visitors to field_group_home_page
 * (AnonymousGroupRedirect), so every group needs one:
 * 1. the node whose D7 url_alias equals the group's short-name slug
 *    (gemm-lab -> node 157, ccgl -> 131, ...);
 * 2. else the lowest top-level book node placed in the group via
 *    og_membership (covers WTG, whose home book carries no slug alias);
 * 3. Main (nid 1) is pinned to node 3, the D7 front page ("home").
 * The result is the D10 nid (source + 400000); groups with no home (About,
 * 0 content) yield nothing and keep the bare 403.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_group_home_page"
 * )
 */
class MmiGroupHomePage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $group_nid = (int) $value;
    if ($group_nid === 1) {
      return 3 + MmiNidOffset::OFFSET;
    }

    $db = Database::getConnection('default', 'migrate_mmi');

    $short = $db->queryRange("SELECT field_short_name_value FROM {field_data_field_short_name}
      WHERE entity_id = :nid AND deleted = 0", 0, 1, [':nid' => $group_nid])->fetchField();
    if ($short) {
      $home = $db->queryRange("SELECT u.source FROM {url_alias} u
        WHERE u.alias = :a AND u.source LIKE 'node/%' ORDER BY u.pid DESC", 0, 1, [':a' => $short])->fetchField();
      if ($home && preg_match('~^node/(\d+)$~', $home, $m)) {
        return (int) $m[1] + MmiNidOffset::OFFSET;
      }
    }

    $book_root = $db->queryRange("SELECT b.bid FROM {book} b
      JOIN {og_membership} m ON m.entity_type = 'node' AND m.etid = b.nid AND m.gid = :gid
      WHERE b.bid = b.nid ORDER BY b.bid", 0, 1, [':gid' => $group_nid])->fetchField();
    if ($book_root) {
      return (int) $book_root + MmiNidOffset::OFFSET;
    }

    return NULL;
  }

}
