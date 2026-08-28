<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\osu_migrations_mmi\Plugin\migrate\process\MmiNidOffset;
use Drupal\webform\Entity\Webform;
use Drupal\webform_migrate\Plugin\migrate\source\d7\D7Webform;

/**
 * D7 webform source confined to the MMI id namespace.
 *
 * The stock D7Webform derives everything from the raw source nid:
 * webform_id becomes "webform_<nid>", and postRollback() walks the SOURCE
 * query loading Node::load(<raw nid>) to detach deleted webforms. Raw MMI
 * nids collide with live agsci nids, so both must ride the +400000 offset —
 * the stock rollback could otherwise touch a live agsci webform node.
 *
 * @MigrateSource(
 *   id = "mmi_d7_webform",
 *   core = {7},
 *   source_module = "webform",
 *   destination_module = "webform"
 * )
 */
class MmiD7Webform extends D7Webform {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $result = parent::prepareRow($row);
    $row->setSourceProperty(
      'webform_id',
      'webform_' . ((int) $row->getSourceProperty('nid') + MmiNidOffset::OFFSET)
    );
    return $result;
  }

  /**
   * {@inheritdoc}
   *
   * Reimplemented from the parent inside the offset namespace: only nodes
   * and webform ids at <nid + OFFSET> — the entities this migration family
   * created — are ever inspected or modified.
   */
  public function postRollback(MigrateRollbackEvent $event) {
    $webforms = $this->query()->execute();
    foreach ($webforms as $webform) {
      $nid = (int) $webform['nid'] + MmiNidOffset::OFFSET;
      $webform_id = 'webform_' . $nid;
      if (empty(Webform::load($webform_id))) {
        $node = Node::load($nid);
        if (!empty($node) && $node->getType() === 'webform') {
          if (!empty($node->webform->target_id) && $node->webform->target_id === $webform_id) {
            $node->webform->target_id = NULL;
            $node->save();
          }
        }
      }
    }
  }

}
