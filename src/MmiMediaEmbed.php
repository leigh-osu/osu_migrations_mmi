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
   * MMI research_project texts embed images and a few videos; documents are
   * included for the remaining node types. Adjust alongside the section-5
   * media migrations if their ids change.
   */
  protected function getMediaMigrations(): array {
    return [
      'mmi_media_images',
      'mmi_media_documents',
      'mmi_media_video',
    ];
  }

}
