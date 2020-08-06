<?php

namespace Drupal\panolens\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exec\ExecTrait;

/**
 * Panolens drush commandfile.
 */
class PanolensCommands extends DrushCommands {

  const LIBRARY_PANOLENS_VERSION = '0.11.0';

  const LIBRARY_THREE_JS_VERSION = 'r105';

  const LIBRARY_PANOLENS_DOWNLOAD_URL = 'https://github.com/pchen66/panolens.js/archive/v' . self::LIBRARY_PANOLENS_VERSION . '.zip';

  const LIBRARY_THREE_JS_DOWNLOAD_URL = 'https://github.com/mrdoob/three.js/archive/' . self::LIBRARY_THREE_JS_VERSION . '.zip';

  const LIBRARY_DESTINATION = 'libraries';

  /**
   * Download and extract Panolens library.
   *
   * @usage panolens-download-panolens
   *   Download Panolens library
   *
   * @command panolens:download-panolens
   * @aliases pldlpl
   */
  public function downloadPanolens() {
    $this->logger()->notice('Downloading Panolens.js library...');
    $this->downloadLibrary(self::LIBRARY_PANOLENS_DOWNLOAD_URL, 'panolens.js');
  }

  /**
   * Download and extract Three.js library.
   *
   * @usage panolens-download-three.js
   *   Download Three.js library
   *
   * @command panolens:download-three.js
   * @aliases pldltj
   */
  public function downloadThreeJs() {
    $this->logger()->notice('Downloading Three.js library...');
    $this->downloadLibrary(self::LIBRARY_THREE_JS_DOWNLOAD_URL, 'three.js');
  }

  /**
   * Download and extract a library.
   *
   * @param string $url
   *   The URL to download.
   * @param string $destination
   *   The path to copy the files to.
   *
   * @throws \Exception
   */
  public function downloadLibrary($url, $destination) {
    if (!is_dir(self::LIBRARY_DESTINATION)) {
      drush_op('mkdir', self::LIBRARY_DESTINATION);
      $this->logger()->notice('Directory ' . self::LIBRARY_DESTINATION . ' was created.');
    }

    // Set the directory to the download location.
    $olddir = getcwd();
    chdir(self::LIBRARY_DESTINATION);

    // Download the archive.
    $filename = basename($url);
    if ($filepath = $this->downloadFile($url, FALSE, FALSE, \Drupal::service('file_system')->getTempDirectory() . '/' . $filename, TRUE)) {
      $filename = basename($url);

      // Remove any existing plugin directory.
      if (is_dir($destination)) {
        \Drupal::service('file_system')->deleteRecursive($destination);
      }

      // Decompress the archive.
      $zip = new \ZipArchive();
      if ($zip->open($filepath) === TRUE) {
        $index = $zip->getNameIndex(0);

        $zip->extractTo('.');
        $zip->close();

        \Drupal::service('file_system')->move($index, $destination);
        $this->logger()->notice('The library has been downloaded to ' . $destination);
      }
      else {
        throw new \Exception("Cannot extract '$filename', not a valid archive");
      }
    }

    // Set working directory back to the previous working directory.
    chdir($olddir);

    if (is_dir(self::LIBRARY_DESTINATION . '/' . $destination)) {
      $this->logger()->info('The plugin has been installed to ' . self::LIBRARY_DESTINATION . '/' . $destination);
    }
    else {
      $this->logger()->error('Drush was unable to install the plugin to ' . self::LIBRARY_DESTINATION . '/' . $destination);
    }
  }

  /**
   * Downloads a file.
   *
   * Optionally uses user authentication, using either wget or curl, as
   * available.
   *
   * @param string $url
   *   The URL to download.
   * @param string $user
   *   The username for authentication.
   * @param string $password
   *   The password for authentication.
   * @param string $destination
   *   The destination folder to copy the download to.
   * @param bool $overwrite
   *   Whether to overwrite an existing destination folder.
   *
   * @return string
   *   The destination folder.
   *
   * @throws \Exception
   */
  protected function downloadFile($url, $user = '', $password = '', $destination = '', $overwrite = TRUE) {
    static $use_wget;
    if ($use_wget === NULL) {
      $use_wget = ExecTrait::programExists('wget');
    }

    $destination_tmp = drush_tempnam('download_file');
    if ($use_wget) {
      $args = ['wget', '-q', '--timeout=30'];
      if ($user && $password) {
        $args = array_merge($args, [
          "--user=$user",
          "--password=$password",
          '-O',
          $destination_tmp,
          $url,
        ]);
      }
      else {
        $args = array_merge($args, ['-O', $destination_tmp, $url]);
      }
    }
    else {
      $args = ['curl', '-s', '-L', '--connect-timeout 30'];
      if ($user && $password) {
        $args = array_merge($args, [
          '--user',
          "$user:$password",
          '-o',
          $destination_tmp,
          $url,
        ]);
      }
      else {
        $args = array_merge($args, ['-o', $destination_tmp, $url]);
      }
    }
    $process = Drush::process($args);
    $process->mustRun();

    if (!Drush::simulate()) {
      if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
        @file_put_contents($destination_tmp, $file);
      }
      if (!drush_file_not_empty($destination_tmp)) {
        // Download failed.
        throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
      }
    }
    if ($destination) {
      \Drupal::service('file_system')
        ->move($destination_tmp, $destination, $overwrite);
      return $destination;
    }
    return $destination_tmp;
  }

}
