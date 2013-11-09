<?php

namespace Lomaswifi\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/admin")
 */
class AdminController extends \Lomaswifi\AdminBundle\Entity\myController {

    /**
     * @Route("/")
     * @Template()
     */
    public function homeAction() {
        return array();
    }

    /**
     * @Template()
     */
    public function mainMenuBarAction() {
        $sections = $this->container->getParameter('lomaswifi.admin.sections');
        return array(
            'sections' => $sections
        );
    }

    /**
     * @Template()
     */
    public function myAccountAction() {
        return array();
    }

    /**
     * @Template()
     */
    public function pagerAction($pager) {
        return $pager;
    }

}

