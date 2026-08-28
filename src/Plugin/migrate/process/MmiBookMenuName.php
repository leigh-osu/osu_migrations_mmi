<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Resolves a book-toc-<nid> menu name to the book's group menu.
 *
 * The agsci og_book_menu plugin matched books to groups BY TITLE and loaded
 * groups by raw D7 nid (agsci preserved nids as group ids). Neither holds
 * for MMI: the book's group comes from its og_membership row in the MMI
 * source, and group ids resolve through the mmi_groups map.
 *
 * @MigrateProcessPlugin(
 *   id = "mmi_book_menu_name"
 * )
 */
class MmiBookMenuName extends MmiGroupMenuName {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $book_nid = (int) substr((string) $value, strlen('book-toc-'));
    $gid = Database::getConnection('default', 'migrate_mmi')
      ->queryRange("SELECT gid FROM {og_membership} WHERE entity_type = 'node' AND etid = :nid", 0, 1, [':nid' => $book_nid])
      ->fetchField();
    if (!$gid) {
      throw new MigrateSkipRowException(sprintf('Book %d belongs to no group; its toc menu has no home.', $book_nid));
    }
    return $this->menuNameForD7Group((int) $gid);
  }

}
