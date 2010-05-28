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
 * @package    Zend_Stdlib
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Stdlib;

/**
 * SignalHandler
 *
 * A handler for a signal, event, filterchain, etc. Abstracts PHP callbacks,
 * primarily to allow for lazy-loading and ensuring availability of default
 * arguments (currying).
 *
 * @category   Zend
 * @package    Zend_Stdlib
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class SignalHandler
{
    /**
     * @var string|array PHP callback to invoke
     */
    protected $_callback;

    /**
     * @var string Signal to which this handle is subscribed
     */
    protected $_signal;

    /**
     * Until callback has been validated, mark as invalid
     * @var bool
     */
    protected $_isValidCallback = false;

    /**
     * Constructor
     * 
     * @param  string $signal Signal to which slot is subscribed
     * @param  string|object $context Function name, class name, or object instance
     * @param  string|null $handler Method name, if $context is a class or object
     * @return void
     */
    public function __construct($signal, $context, $handler = null)
    {
        $this->_signal = $signal;

        if (null === $handler) {
            $this->_callback = $context;
        } else {
            $this->_callback = array($context, $handler);
        }
    }

    /**
     * Get signal to which slot is subscribed
     * 
     * @return string
     */
    public function getSignal()
    {
        return $this->_signal;
    }

    /**
     * Retrieve registered callback
     * 
     * @return Callback
     * @throws InvalidCallbackException
     */
    public function getCallback()
    {
        if ($this->_isValidCallback) {
            return $this->_callback;
        }

        $callback = $this->_callback;
        if (is_string($callback)) {
            return $this->_validateStringCallback($callback);
        }
        if (is_array($callback)) {
            return $this->_validateArrayCallback($callback);
        }
        if (is_callable($callback)) {
            $this->_isValidCallback = true;
            return $callback;
        }
        throw new InvalidCallbackException('Invalid callback provided; not callable');
    }

    /**
     * Invoke handler
     * 
     * @param  array $args Arguments to pass to callback
     * @return mixed
     */
    public function call(array $args = array())
    {
        $callback = $this->getCallback();

        // Following is a performance optimization hack. call_user_func() is up 
        // to 6x faster than call_user_func_array(), and since signal handlers 
        // will be called potentially dozens to hundreds of times in an 
        // application, this optimization makes sense.
        //
        // We can potentially expand the number of case statements if we find 
        // many instances where we're using more arguments.
        switch (count($args)) {
            case 1:
                return call_user_func($callback, $args[0]);
            case 2:
                return call_user_func($callback, $args[0], $args[1]);
            case 3:
                return call_user_func($callback, $args[0], $args[1], $args[2]);
            default:
                return call_user_func_array($callback, $args);
        }
    }

    /**
     * Validate a string callback
     *
     * Check first if the string provided is callable. If not see if it is a 
     * valid class name; if so, determine if the object is invokable.
     * 
     * @param  string $callback 
     * @return Callback
     * @throws InvalidCallbackException
     */
    protected function _validateStringCallback($callback)
    {
        if (is_callable($callback)) {
            $this->_isValidCallback = true;
            return $callback;
        }

        if (!class_exists($callback)) {
            throw new InvalidCallbackException('Provided callback is not a function or a class');
        }

        $object = new $callback();
        if (!is_callable($object)) {
            throw new InvalidCallbackException('Class provided as a callback does not implement __invoke');
        }

        $this->_callback        = $object;
        $this->_isValidCallback = true;
        return $object;
    }

    /**
     * Validate an array callback
     * 
     * @param  array $callback 
     * @return callback
     * @throws InvalidCallbackException
     */
    protected function _validateArrayCallback(array $callback)
    {
        $context = $callback[0];
        $method  = $callback[1];

        if (is_string($context)) {
            // Dealing with a class/method callback, and class provided is a string classname
            
            if (!class_exists($context)) {
                throw new InvalidCallbackException('Class provided in callback does not exist');
            }

            // We need to determine if we need to instantiate the class first
            $r = new \ReflectionClass($context);
            if (!$r->hasMethod($method)) {
                // Explicit method does not exist
                if (!$r->hasMethod('__callStatic') && !$r->hasMethod('__call')) {
                    throw new InvalidCallbackException('Class provided in callback does not define the method requested');
                }

                if ($r->hasMethod('__callStatic')) {
                    // We have a __callStatic defined, so the original callback is valid
                    $this->_isValidCallback = true;
                    return $callback;
                }

                // We have __call defined, so we need to instantiate the class 
                // first, and redefine the callback
                $object                 = new $context();
                $this->_callback        = array($object, $method);
                $this->_isValidCallback = true;
                return $this->_callback;
            }

            // Explicit method exists
            $rMethod = $r->getMethod($method);
            if ($rMethod->isStatic()) {
                // Method is static, so original callback is fine
                $this->_isValidCallback = true;
                return $callback;
            }

            // Method is an instance method; instantiate object and redefine callback
            $object                 = new $context();
            $this->_callback        = array($object, $method);
            $this->_isValidCallback = true;
            return $this->_callback;
        } elseif (is_callable($callback)) {
            // The 
            $this->_isValidCallback = true;
            return $callback;
        }


        throw new InvalidCallbackException('Method provided in callback does not exist in object');
    }
}