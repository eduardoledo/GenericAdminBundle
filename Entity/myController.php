<?php

namespace Lomaswifi\AdminBundle\Entity;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Session;
use MakerLabs\Bundle\PagerBundle\Pager;
use MakerLabs\Bundle\PagerBundle\Adapter\DoctrineOrmAdapter;

class myController extends Controller
{

    protected $index_query = NULL;
    protected $index_template = 'LomaswifiAdminBundle:Admin:index_default.html.twig';
    protected $new_template = 'LomaswifiAdminBundle:Admin:new_default.html.twig';
    protected $edit_template = 'LomaswifiAdminBundle:Admin:edit_default.html.twig';
    protected $index_title = '';
    protected $entity = NULL;
    protected $class_name = NULL;
    protected $form_class_name = NULL;
    protected $fields = array();
    protected $section = '';

    public function __construct()
    {
        ;
    }

    /**
     * @Route("/{page}", requirements={"page" = "\d+"}, defaults={"page" = 1})
     * @Template()
     */
    public function indexAction($page)
    {
        $this->loadConfig();

        $request = $this->getRequest();
        $route = $request->get('_route');
        if ($this->getRequest()->getMethod() == 'POST') {
            $rows_per_page = $this->getRequest()->get('rows_per_page', 0);
            $order = $this->getRequest()->get('orderby', array());
            $refresh = FALSE;
            if ($rows_per_page > 0) {
                $this->setRowsPerPage($rows_per_page);
                $refresh = TRUE;
            }

            if (count($order) > 0) {
                $this->setOrder($order);
                $refresh = TRUE;
            }


            if ($refresh) {
                $params = $request->query->all();
                $_POST = array();
                return $this->redirect($this->generateUrl($route, $params));
            }
        }

        if (is_null($this->index_query) && !is_null($this->entity)) {
            $em = $this->getDoctrine()->getManager();
            $this->index_query = $em->getRepository($this->entity)
                    ->createQueryBuilder('e');
        }

        if (!is_null($this->index_query)) {
            $rows_per_page = $this->getRowsPerPage();
            $order = $this->getOrder();

            if (count($order) > 0) {
                foreach ($order as $key => $value) {
                    $this->index_query->addOrderBy($key, $value);
                }
            }

            $adapter = new DoctrineOrmAdapter($this->index_query);
            $pager = new Pager($adapter, array('page' => $page, 'limit' => $rows_per_page));

            if ($pager->isPaginable()) {
                $this->getSession()->setFlash('pager', array(
                    'current' => $page,
                    'last' => $pager->getLastPage(),
                    'route' => $route,
                    'rows_per_page' => $rows_per_page
                ));
            }
            $this->getSession()->setFlash('show_rows_per_page', $pager->hasResults());

            return $this->render($this->index_template, array(
                        'pager' => $pager,
                        'fields' => $this->fields,
                        'title' => $this->index_title,
                        'url_edit' => substr($this->getRequest()->get('_route'), 0, strrpos($this->getRequest()->get('_route'), '_')) . '_edit',
                        'url_delete' => substr($this->getRequest()->get('_route'), 0, strrpos($this->getRequest()->get('_route'), '_')) . '_delete',
                        'delete_form' => $this->createDeleteForm()
                    ));
        }

        return FALSE;
    }

    /**
     * @Route("/new")
     */
    public function newAction()
    {
        $this->loadConfig();

        if (!is_null($this->class_name) && !is_null($this->form_class_name)) {
            $entity = new $this->class_name();
            $request = $this->getRequest();
            if (is_object($this->form_class_name)) {
                $form = $this->form_class_name;
            } else {
                $form = $this->createForm(new $this->form_class_name(), $entity);
            }

            return $this->render($this->new_template, array(
                        'entity' => $entity,
                        'form' => $form->createView(),
                        'title' => $this->index_title,
                        'url_create' => substr($request->get('_route'), 0, strrpos($request->get('_route'), '_')) . '_create'
                    ));
        }
    }

    /**
     * @Route("/create")
     * @Method("post")
     */
    public function createAction()
    {
        $this->loadConfig();

        if (!is_null($this->class_name) && !is_null($this->form_class_name)) {
            $entity = new $this->class_name();
            $request = $this->getRequest();
            $form = $this->createForm(new $this->form_class_name(), $entity);
            $form->bindRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                try {
                    $em->flush();
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }


                return $this->redirect($this->generateUrl(substr($request->get('_route'), 0, strrpos($request->get('_route'), '_')) . '_index'));
            }
            return $this->render($this->new_template, array(
                        'entity' => $entity,
                        'form' => $form->createView(),
                        'title' => $this->index_title,
                        'url_create' => substr($request->get('_route'), 0, strrpos($request->get('_route'), '_')) . '_create'
                    ));
        }
    }

    /**
     * @Route("/{id}/edit")
     */
    public function editAction($id)
    {
        $this->loadConfig();

        if (!is_null($this->entity)) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository($this->entity)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Author entity.');
            }
            if (is_object($this->form_class_name)) {
                $editForm = $this->form_class_name;
                $editForm->setData($entity);
            } else {
                $editForm = $this->createForm(new $this->form_class_name(), $entity);
            }

            return $this->render($this->edit_template, array(
                        'entity' => $entity,
                        'form' => $editForm->createView(),
                        'url_update' => substr($this->getRequest()->get('_route'), 0, strrpos($this->getRequest()->get('_route'), '_')) . '_update',
                        'title' => 'Editar'
                    ));
        }
    }

    /**
     * @Route("/{id}/update")
     * @Method("post")
     */
    public function updateAction($id)
    {
        $this->loadConfig();

        if (!is_null($this->entity)) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository($this->entity)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find entity.');
            }

            $editForm = $this->createForm(new $this->form_class_name(), $entity);
            $deleteForm = $this->createDeleteForm($id);

            $request = $this->getRequest();

            $editForm->bindRequest($request);

            if ($editForm->isValid()) {
                $em->persist($entity);
                $em->flush();
                $url = substr($this->getRequest()->get('_route'), 0, strrpos($this->getRequest()->get('_route'), '_')) . '_index';

                return $this->redirect($this->generateUrl($url));
            }

            return $this->render($this->edit_template, array(
                        'entity' => $entity,
                        'edit_form' => $editForm->createView(),
                        'delete_form' => $deleteForm->createView(),
                    ));
        }
    }

    /**
     * @Route("/{id}/delete")
     * @Method("post")
     */
    public function deleteAction($id)
    {
        $this->loadConfig();

        if (!is_null($this->entity)) {
            $form = $this->createDeleteForm($id);
            $request = $this->getRequest();

            $form->bindRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $entity = $em->getRepository($this->entity)->find($id);

                if (!$entity) {
                    throw $this->createNotFoundException('Unable to find entity.');
                }

                $em->remove($entity);
                $em->flush();
            }
        }

        $url = substr($this->getRequest()->get('_route'), 0, strrpos($this->getRequest()->get('_route'), '_')) . '_index';

        return $this->redirect($this->generateUrl($url));
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->get('session');
    }

    protected function loadConfig()
    {
        $sections = $this->container->getParameter('lomaswifi.admin.sections');
        if (isset($sections[$this->section])) {
            $section = $sections[$this->section];
            $this->index_title = $section['title'];
            $this->fields = $section['fields'];
            $this->entity = $section['entity'];

            $em = $this->getDoctrine()->getManager();
            $meta = $em->getClassMetadata($this->entity);
            $this->class_name = $meta->getName();

            if (isset($section['form_class'])) {
                $this->form_class_name = $section['form_class'];
            } elseif (isset($section['form_service']) && $this->has($section['form_service'])) {
                $this->form_class_name = $this->get($section['form_service']);
            }
        }
    }

    protected function setRowsPerPage($rows)
    {
        $rows = intval($rows);
        if ($rows > 0) {
            $this->getSession()->set('rows_per_page/' . sha1(get_called_class()), $rows);
        }
    }

    protected function getRowsPerPage()
    {
        $rows_per_page = $this->getSession()->get('rows_per_page/' . sha1(get_called_class()), 10);
        if ($rows_per_page == 10) {
            $this->setRowsPerPage($rows_per_page);
        }
        return $rows_per_page;
    }

    protected function setOrder($field, $order)
    {
        if (in_array(strtoupper($order), array('ASC', 'DESC')) && trim($field) != '') {
            $orderby = $this->getOrder();
            if (isset($orderby[$field])) {
                if ($orderby[$field] == 'ASC') {
                    $orderby[$field] == 'DESC';
                } else {
                    unset($orderby[$field]);
                }
            } else {
                $orderby[$field] == 'ASC';
            }
            $this->getSession()->set('order/' . sha1(get_called_class()), $orderby);
        }
    }

    protected function getOrder()
    {
        return $this->getSession()->get('order/' . sha1(get_called_class()), array());
    }

    protected function clearOrder()
    {
        $this->getSession()->remove('order/' . sha1(get_called_class()));
    }

    protected function createDeleteForm($id = 0)
    {
        return $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm()
        ;
    }

}
