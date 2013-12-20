<?php
return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'zf2-email' => array(
                    'options' => array(
                        'route'    => 'zf2-email [--action|-a]',
                        'defaults' => array(
                            'controller' => 'ZF2Email\Controller\Index',
                            'action'     => 'index'
                        )
                    )
                )
            )
        )
    ),
    
    'di' => array(
        'instance' => array(
            'Email\Email' => array(
                'parameters' => array(
                    'viewsDir'  => array(
                        dirname(__DIR__) . '/view/html-email/example'
                    ),
                    'transport'         => 'email-transport',
                    'message'           => 'email-message',
                    'templateRenderer'  => 'email-renderer',
                    'template'          => 'example'
                )
            ),
            'aliases' => array(
                'email'                 => 'Email\Email',
                'email-factory'         => 'Email\EmailFactory',
                'email-transport'       => 'Zend\Mail\Transport\SendMail',
                'email-message'         => 'Zend\Mail\Message',
                'email-renderer'        => 'Zend\View\Renderer\PhpRenderer'
            )
        ),
    )
);
