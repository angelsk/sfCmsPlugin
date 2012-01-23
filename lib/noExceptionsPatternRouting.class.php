<?php

/**
 * A version of sfPatternRouting which does not throw annoying exceptions when
 * a route does not exist, and logs it instead.
 */
class noExceptionsPatternRouting extends sfPatternRouting
{
  /**
   * @see sfPatternRouting
   */
  public function generate($name, $params = array(), $absolute = false)
  {
    try
    {
      return parent::generate($name, $params, $absolute)
    }
    catch (sfConfigurationException $e)
    {
      if (sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->warning('noExceptionsPatternRouting : ' . $e->getMessage());
      }

      return '#';
    }
  }
}