<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\taxonomy\Plugin\migrate\source\d7\Term;

/**
 * D7 taxonomy terms restricted to those a field actually references.
 *
 * The mmi functional_groups vocabulary carries 22 terms but only 17 are used
 * by any profile; the unused five (Senior Research, Research Assistant,
 * Stranding Coordinator, Affiliates, Instructor) would only clutter the
 * shared membership_types vocabulary. The `reference_field` configuration
 * names the D7 field whose field_data_ table must hold at least one row
 * pointing at the term for it to migrate; a later source refresh that starts
 * using one picks it up on the next run.
 *
 * @MigrateSource(
 *   id = "mmi_d7_term_referenced",
 *   source_module = "taxonomy"
 * )
 */
class MmiTermReferenced extends Term {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $field = $this->configuration['reference_field'] ?? NULL;
    if ($field) {
      $query->exists(
        $this->select('field_data_' . $field, 'ref')
          ->fields('ref', ['entity_id'])
          ->where("ref.{$field}_tid = td.tid")
          ->condition('ref.deleted', 0)
      );
    }
    return $query;
  }

}
