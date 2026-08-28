<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\osu_migrations_cas\Plugin\migrate\source\CasD7WebformSubmission;
use Drupal\osu_migrations_mmi\Plugin\migrate\process\MmiNidOffset;

/**
 * D7 webform submission source for the MMI webforms.
 *
 * Inherits the CAS fixes (lowercased data keys matching D7Webform's element
 * keys, file-component fid remapping) and retargets the namespace: the
 * webform_id and submission uri point at the offset MMI ids, and fids
 * resolve through the mmi file maps instead of the agsci ones.
 *
 * @MigrateSource(
 *   id = "mmi_d7_webform_submission",
 *   core = {7},
 *   source_module = "webform",
 *   destination_module = "webform"
 * )
 */
class MmiD7WebformSubmission extends CasD7WebformSubmission {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);
    $nid = (int) $row->getSourceProperty('nid') + MmiNidOffset::OFFSET;
    $row->setSourceProperty('webform_id', 'webform_' . $nid);
    $row->setSourceProperty('webform_uri', '/node/' . $nid);
    return $result;
  }

  /**
   * {@inheritdoc}
   *
   * MMI submission uploads live in the mmi file maps: the 12 private
   * stranding photos in mmi_files_private, everything else (85 public
   * files) in mmi_files. 57 stored fids dangle (files deleted in D7) and
   * pass through unchanged, same as the parent's behaviour for unmapped
   * fids.
   */
  protected function remapFileId($fid) {
    if (!ctype_digit((string) $fid)) {
      return $fid;
    }
    $db = \Drupal::database();
    foreach (['migrate_map_mmi_files_private', 'migrate_map_mmi_files'] as $table) {
      if ($db->schema()->tableExists($table)) {
        $dest = $db->query('SELECT destid1 FROM {' . $table . '} WHERE sourceid1 = :s', [':s' => (int) $fid])->fetchField();
        if ($dest) {
          return $dest;
        }
      }
    }
    return $fid;
  }

}
