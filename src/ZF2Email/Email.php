<?php
namespace ZF2Email;

use 
    Zend\View\Resolver\TemplatePathStack as TemplateResolver,
    Zend\View\Model\ViewModel,
    Zend\Mime\Part as MimePart,
    Zend\Mime\Message as MimeMessage,
    ZF2Email\Exception\InvalidMethodException,
    ZF2Email\Exception\MailNotReadyException;

class Email
{
    /**
     * @var \Zend\Mail\Transport\TransportInterface
     */
    protected $transport;

    /**
     * @var \Zend\Mail\Message
     */
    protected $message;

    /**
     * List of directories where to look for email templates
     *
     * @var array
     */
    protected $viewsDir = array();

    /**
     * @var string
     */
    protected $template;
    
    /**
     * @var string
     */
    protected $layout;

    /**
     * @var \Zend\View\Renderer\RendererInterface
     */
    protected $templateRenderer;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @param \Zend\Mail\Transport\TransportInterface $transport
     */
    public function setTransport(\Zend\Mail\Transport\TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param \Zend\Mail\Message $message
     */
    public function setMessage(\Zend\Mail\Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return \Zend\Mail\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $viewsDir
     * @return void
     */
    public function setViewsDir($viewsDir)
    {
        $this->viewsDir = $viewsDir;
    }

    /**
     * @return array
     */
    public function getViewsDir()
    {
        return $this->viewsDir;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param $entityManager
     * @return void
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    
    /**
     * @param \Zend\View\Renderer\RendererInterface $templateRenderer
     */
    public function setTemplateRenderer(\Zend\View\Renderer\RendererInterface $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    /**
     * @return \Zend\View\Renderer\RendererInterface
     */
    public function getTemplateRenderer()
    {
        return $this->templateRenderer;
    }

    /**
     * @param array $params
     * @param null $overrideTo
     * @param null $overrideToName
     * @throws MailNotReadyException
     * @throws \Zend\Mail\Transport\Exception\RuntimeException
     */
    public function send(array $params = array(), $overrideTo = null, $overrideToName = null)
    {
        if (!$this->transport instanceof \Zend\Mail\Transport\TransportInterface) {
            throw new MailNotReadyException("The email transport was not defined");
        }

        if (!$this->message instanceof \Zend\Mail\Message) {
            throw new MailNotReadyException("No email message was defined");
        }

        if (!count($this->viewsDir)) {
            throw new MailNotReadyException("No template stack directories defined in viewsDir");
        }

        if (!$this->template) {
            throw new MailNotReadyException("No template was defined");
        }

        
        $headers = $this->message->getHeaders();
        
        $headers->removeHeader('Content-Type');
        $headers->removeHeader('Content-Transfer-Encoding');
        
        $headers->addHeaderLine('Content-Transfer-Encoding', '8bit');
        $headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');
        
        $subject = mb_encode_mimeheader(utf8_decode($this->message->getSubject()), "UTF-8");
        
        $this->message->setSubject($subject);
        
        $this->message->setBody($this->createBody($params));

        if (null !== $overrideTo && null !== $overrideToName) {
            $this->message->setTo($overrideTo, $overrideToName);
        } else if (null !== $overrideTo) {
            $this->message->setTo($overrideTo);
        }

        if (!count($this->getMessage()->getTo())) {
            throw new MailNotReadyException("Vous devez sélectionner un destinataire.");
        }

        $this->transport->send($this->message);
    }

    /**
     * @param array $params
     * @return \Zend\Mime\Message
     */
    public function createBody(array $params = array())
    {
        $this->templateRenderer->setResolver(new TemplateResolver(array('script_paths' => $this->viewsDir)));
        
        $contentViewModel = new ViewModel($params);
        $contentViewModel->setTemplate('templates/'.$this->template);
        if($this->layout)
        {
            $layoutViewModel = new ViewModel();
            $layoutViewModel->setVariable('content', $this->templateRenderer->render($contentViewModel));
            $layoutViewModel->setTemplate('layouts/'.$this->layout);
            
            $resultViewModel = $layoutViewModel;
        }
        else
            $resultViewModel = $contentViewModel;
        
        $html = new MimePart($this->formatHtml($this->templateRenderer->render($resultViewModel)));
        $html->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts(array($html));

        return $body;
    }

    /**
     * @param string $string
     * @return string
     */
    public function formatHtml($string)
    {
        $string = str_replace(array("\n", "\r"), "", $string);
        $string = str_replace("<","\r\n<", $string);
        return wordwrap($string, 78, "\r\n");
    }
    
    /**
     * @throws InvalidMethodException
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->message, $name)) {
            return call_user_func_array(array($this->message, $name), $arguments);
        }

        throw new InvalidMethodException("Method {$name}() does not exist");
    }
}
