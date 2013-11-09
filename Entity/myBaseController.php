<?php

namespace Lomaswifi\AdminBundle\Entity;

use Symfony\Component\HttpFoundation\Session;

/**
 * Description of myBaseController
 *
 * @author eduardoledo
 */
class myBaseController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{

    /**
     * @return Session\Session
     */
    public function getSession()
    {
        return $this->get('session');
    }

}

