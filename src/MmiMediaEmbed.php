<?php

namespace Drupal\osu_migrations_mmi;

use Drupal\osu_migrations\OsuMediaEmbed;

/**
 * Media embed transform reading the MMI media maps, never the agsci ones.
 *
 * The parent resolves a D7 media-token fid through the agsci media
 * migrations. MMI fids collide with agsci fids (750 shared values), so a
 * token in MMI text must only ever be looked up in the mmi_* media maps.
 */
class MmiMediaEmbed extends OsuMediaEmbed {

  /**
   * {@inheritdoc}
   *
   * The five mmi media migrations; MMI has no private-scheme media (its only
   * private files are webform submission uploads, which do not migrate).
   */
  protected function getMediaMigrations(): array {
    return [
      'mmi_media_images',
      'mmi_media_documents',
      'mmi_media_local_video',
      'mmi_media_remote_video',
      'mmi_media_kaltura',
    ];
  }

}
