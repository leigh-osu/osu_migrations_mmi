<?php

namespace Drupal\osu_migrations_mmi\Plugin\migrate\source;

use Drupal\osu_migrations_cas\Plugin\migrate\source\CasMediaCaptionSeed;

/**
 * MMI variant of the caption seed: only MMI-migrated image media.
 *
 * The parent seeds field_media_caption from the media's own alt/title
 * wherever the caption is empty; unscoped it would sweep agsci media too
 * (already seeded during the agsci rebuild, but any media an editor has
 * since deliberately left caption-less must stay that way). Scope to the
 * MMI image-media map.
 *
 * @MigrateSource(
 *   id = "mmi_media_caption_seed",
 *   source_module = "media"
 * )
 */
class MmiMediaCaptionSeed extends CasMediaCaptionSeed {

  /**
   * {@inheritdoc}
   */
  protected function scopeMids(): ?array {
    $scope = [];
    foreach ($this->d10->query('SELECT destid1 FROM {migrate_map_mmi_media_images} WHERE destid1 IS NOT NULL')->fetchCol() as $mid) {
      $scope[(int) $mid] = TRUE;
    }
    return $scope;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'mmi_media_caption_seed';
  }

}
