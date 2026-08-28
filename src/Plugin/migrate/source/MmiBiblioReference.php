<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\osu_migrate_content\Plugin\migrate\source\OsuBiblioReference;

/**
 * MMI biblio reference source.
 *
 * The agsci run used cas_biblio_reference_domain, whose additions over the
 * stock source are (a) D7 domain assignment data and (b) the contributor
 * role split, node body and node columns. MMI has no domain tables (the
 * whole site lands on the mmi domain constant), so this subclass extends the
 * stock source and carries over only (b) — the editor split, the node body
 * and the node/node_revision columns the mmi_content group process expects
 * (uid, language, status, revision authorship). Everything is read over the
 * migration's own connection (migrate_mmi via the group source key).
 *
 * @MigrateSource(
 *   id = "mmi_biblio_reference",
 *   source_provider = "biblio",
 *   source_module = "biblio"
 * )
 */
class MmiBiblioReference extends OsuBiblioReference {

  /**
   * Contributor auth_types that are editors of the containing work.
   *
   * Same convention as CasBiblioReferenceDomain: Secondary Author (2) is
   * biblio's EndNote-style editor of the containing volume, Series Editor
   * (10) and Editor (14) are literal, and anything placed in the secondary
   * slot (auth_category 2) renders as an editor regardless of type.
   */
  protected const EDITOR_AUTH_TYPES = [2, 10, 14];

  /**
   * {@inheritdoc}
   *
   * The parent selects only b.* and n.title. The mmi_content group process
   * maps uid/status/promote/sticky/created/changed/langcode from the node
   * row and revision authorship from node_revision, so select them all.
   * n.changed also backs the high_water property.
   */
  public function query() {
    $query = parent::query();
    $query->fields('n', [
      'type', 'language', 'status', 'promote', 'sticky', 'created', 'changed',
    ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->leftJoin('node_revision', 'nr', 'nr.vid = n.vid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->fields('nr', ['log', 'timestamp']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['editors'] = $this->t('Editors (contributors with an editor auth_type)');
    $fields['body'] = $this->t('Node body (the standard field, not biblio_full_text)');
    $fields['body_format'] = $this->t('Node body text format');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);
    $nid = $row->getSourceProperty('nid');

    // Re-split contributors by role: the parent's selectContributors()
    // merges every contributor into 'author' regardless of auth_type, which
    // reads the editors of edited volumes and chapters as co-authors. Rows
    // carry cid for the mmi_biblio_authors term lookup.
    $query = $this->select('biblio_contributor', 'bc');
    $query->fields('bc', ['auth_type', 'auth_category', 'cid']);
    $query->fields('bcd', ['name']);
    $query->innerJoin('biblio_contributor_data', 'bcd', 'bc.cid = bcd.cid');
    $query->condition('bc.nid', $nid);
    $query->condition('bc.vid', $row->getSourceProperty('vid'));
    $query->orderBy('bc.rank');
    $authors = [];
    $editors = [];
    foreach ($query->execute() as $record) {
      $item = ['cid' => $record['cid'], 'name' => $record['name']];
      if (in_array((int) $record['auth_type'], static::EDITOR_AUTH_TYPES, TRUE)
        || (int) $record['auth_category'] === 2) {
        $editors[] = $item;
      }
      else {
        $authors[] = $item;
      }
    }
    $row->setSourceProperty('author', $authors);
    $row->setSourceProperty('editors', $editors);

    // The node body: biblio_full_text is a flag, the text lives in the
    // ordinary body field, which the DrupalSqlBase-derived parent never
    // fetches (same recovery as the agsci run's alter).
    $body = $this->select('field_data_body', 'b')
      ->fields('b', ['body_value', 'body_format'])
      ->condition('b.entity_type', 'node')
      ->condition('b.entity_id', $nid)
      ->condition('b.deleted', 0)
      ->orderBy('b.delta')
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();
    if ($body && trim((string) $body['body_value']) !== '') {
      $row->setSourceProperty('body', $body['body_value']);
      $row->setSourceProperty('body_format', $body['body_format']);
    }

    return $result;
  }

}
