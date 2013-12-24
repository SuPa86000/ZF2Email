<?php
namespace ZF2Email;

use Zend\Di\Di,
    Zend\ServiceManager\ServiceLocatorAwareInterface,
    Zend\ServiceManager\ServiceLocatorInterface,
    ZF2Email\Exception\BadMethodCallException,
    ZF2Email\Exception\InvalidMethodException,
    ZF2Email\Exception\InvalidArgumentException;

class EmailFactory implements ServiceLocatorAwareInterface
{
    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;
    
    /**
     * @param array $options
     * @return \Email\Email
     * @throws Exception\BadMethodCallException
     * @throws Exception\InvalidArgumentException
     */
    public function create(array $options = array())
    {
        $di = $this->serviceLocator->get('di');
        
        if (!$di instanceof Di)
            throw new BadMethodCallException("No Di container found in the service locator");
        
        $email = $di->newInstance('email');
        
        $email->setTemplateRenderer($this->serviceLocator->get('Zend\View\Renderer\RendererInterface'));
        
        foreach ($options as $opt => $val)
        {
            if (!is_array($val))
                $val = array($val);

            try
            {
                call_user_func_array(array($email, 'set' . ucfirst($opt)), $val);
            }
            catch (InvalidMethodException $e)
            {
                throw new InvalidArgumentException("No option {$opt} available for the Email or Message class");
            }
        }
        
        return $email;
    }

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}