<?php

/**
 * ContentBlockTable
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
abstract class PluginContentBlockTable extends Doctrine_Table
{
  /**
   * Returns an instance of this class.
   *
   * @return object ContentBlockTable
   */
  public static function getInstance()
  {
    return Doctrine_Core::getTable('ContentBlock');
  }

  /**
   * Load Content blocks and current versions for rendering
   */
  public function loadAllCurrentVersions($id, $lang)
  {
    $query = $this->createQuery('b')
                  ->innerJoin('b.Versions bv')
                  ->innerJoin('bv.CurrentVersion cv')
                  ->where('b.content_group_id = ? AND (bv.lang = ? OR bv.lang IS NULL) AND (cv.lang = ? OR cv.lang IS NULL)', array($id, $lang, $lang));

    return $query->execute();
  }
}