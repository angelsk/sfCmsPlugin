<?php

/**
 * PluginSiteApprovalTable
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class PluginSiteApprovalTable extends Doctrine_Table
{
  /**
   * Returns an instance of this class.
   *
   * @return object PluginSiteApprovalTable
   */
  public static function getInstance()
  {
    return Doctrine_Core::getTable('PluginSiteApproval');
  }
  
  /**
   * Get the latest active approval for the model
   * 
   * @param string $model
   * @param int $modelId
   * @return SiteApproval
   */
  public function findLatest($model, $modelId)
  {
    $q = $this->createQuery('a')
              ->where('model = ? AND model_id = ? AND deleted_at IS NULL', array($model, $modelId));
              
    return $q->fetchOne();
  }
  
  /**
   * Index approvals for site by sitetree_id for display on index
   * 
   * @param string $site
   * @return array()
   */
  public function getOrderedApprovalsForSite($site)
  {
    $q = $this->createQuery('a')
              ->select('COUNT(a.id) AS count, a.sitetree_id')
              ->innerJoin('a.Sitetree s')
              ->where('s.site = ? AND a.deleted_at IS NULL', array($site))
              ->orderBy('a.sitetree_id')
              ->groupBy('a.sitetree_id');
              
    $r = $q->execute(null, Doctrine_Core::HYDRATE_ARRAY);
    $s = array();
    
    foreach ($r as $a)
    {
      $s[$a['sitetree_id']] = $a['count']; 
    }
    
    return $s;
  }
  
  /**
   * Index approvals for sitetree by model_id for display on index
   * 
   * @param string $site
   * @return array()
   */
  public function getOrderedApprovalsForItem($sitetreeId, $model)
  {
    $q = $this->createQuery('a')
              ->select('COUNT(a.id) AS count, a.model_id')
              ->where('a.sitetree_id = ? AND a.model = ? AND a.deleted_at IS NULL', array($sitetreeId, $model))
              ->orderBy('a.model_id')
              ->groupBy('a.model_id');
              
    $r = $q->execute(null, Doctrine_Core::HYDRATE_ARRAY);
    $s = array();
    
    foreach ($r as $a)
    {
      $s[$a['model_id']] = $a['count']; 
    }
    
    return $s;
  }
}