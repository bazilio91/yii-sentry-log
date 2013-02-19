<?php

/**
 * RSentryComponent records exceptions to sentry server.
 *
 * RSentryComponent can be used with RSentryLog but only tracts exceptions
 * as Yii logger does not pass the exception to the logger but rather a string traceback
 * RSentryLog "error" logging is not that usefull as the traceback
 * does not contain veriables but only a string where this component allows you to use
 * the power of sentry for exceptions.
 *
 * @author Pieter Venter <boontjiesa@gmail.com>
 */
class RSentryComponent extends CApplicationComponent
{
    /**
     * @var string Sentry DSN value
     */
    public $dsn;

    /**
     * @var Raven_Client Sentry stored connection
     */
    protected $_client;

    /**
     * @var string
     */
    public $logger = 'php';

    /**
     * Initializes the connection.
     */
    public function init()
    {
        parent::init();

        if (!class_exists('Raven_Autoloader', false)) {
            spl_autoload_unregister(array('YiiBase', 'autoload'));
            include(dirname(__FILE__) . '/raven-php/lib/Raven/Autoloader.php');
            Raven_Autoloader::register();
            spl_autoload_register(array('YiiBase', 'autoload'));
        }

        if ($this->_client === null) {
            $this->_client = new Raven_Client($this->dsn, array('logger' => $this->logger));
        }

        Yii::app()->attachEventHandler('onException', array($this, 'handleException'));
        Yii::app()->attachEventHandler('onError', array($this, 'handleError'));

        $error_handler = new Raven_ErrorHandler($this->_client);
        $error_handler->registerShutdownFunction();
    }

    /**
     * logs exception
     * @param CExceptionEvent $event Description
     */
    public function handleException($event)
    {
        $this->_client->captureException($event->exception);
        if ($this->_client->getLastError()) {
            Yii::log($this->_client->getLastError(), CLogger::LEVEL_ERROR, 'raven');
        }
    }

    /**
     * @param CErrorEvent $event
     */
    public function handleError($event)
    {
        $e = new ErrorException($event->message, $event->code, 0, $event->file, $event->line);
        $this->_client->captureException($e);
        if ($this->_client->getLastError()) {
            Yii::log($this->_client->getLastError(), CLogger::LEVEL_ERROR, 'raven');
        }
    }
}
