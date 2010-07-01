<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Tool
 * @subpackage Framework
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\Tool\Framework\Provider;

/**
 * This is a convenience class.
 *
 * At current it will return the request and response from the client registry
 * as they are the more common things that will be needed by providers
 *
 *
 * @uses       \Zend\Tool\Framework\Provider\ProviderInterface
 * @uses       \Zend\Tool\Framework\Registry\EnabledInterface
 * @category   Zend
 * @package    Zend_Tool
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AbstractProvider
    implements ProviderInterface, \Zend\Tool\Framework\Registry\EnabledInterface
{

    /**
     * @var \Zend\Tool\Framework\Registry\RegistryInterface
     */
    protected $_registry = null;

    /**
     * setRegistry() - required by Zend_Tool_Framework_Registry_EnabledInterface
     *
     * @param \Zend\Tool\Framework\Registry\RegistryInterface $registry
     * @return \Zend\Tool\Framework\Provider\AbstractProvider
     */
    public function setRegistry(\Zend\Tool\Framework\Registry\RegistryInterface $registry)
    {
        $this->_registry = $registry;
        return $this;
    }


}