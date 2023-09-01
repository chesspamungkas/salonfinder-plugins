<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 3/3/2019
 * Time: 7:02 PM
 */

namespace SF\core;


class SF_ShortCode
{
  public function render($pathAlias, $params=[]) {
    $viewFile = path_join(SF_BASEPATH,$pathAlias).'.php';
    $returnText = '';
    if (file_exists($viewFile)) {
      try {
        $returnText = $this->renderPhpFile($viewFile, $params);
      } catch(\Exception $e) {

      }
    }
    return $returnText;
  }

  public function renderPhpFile($_file_, $_params_ = [])
  {
    $_obInitialLevel_ = ob_get_level();
    ob_start();
    ob_implicit_flush(false);
    extract($_params_, EXTR_OVERWRITE);
    try {
      require $_file_;
      return ob_get_clean();
    } catch (\Exception $e) {
      while (ob_get_level() > $_obInitialLevel_) {
        if (!@ob_end_clean()) {
          ob_clean();
        }
      }
      throw $e;
    } catch (\Throwable $e) {
      while (ob_get_level() > $_obInitialLevel_) {
        if (!@ob_end_clean()) {
          ob_clean();
        }
      }
      throw $e;
    }
  }
}