<?php

namespace PFC\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
*  » BaseController es la clase de la que deben heredar todos los controladores.
*    Define comportamientos y métodos comunes para todos ellos
**/
abstract class BaseController extends Controller
{

    protected $container     = null;
    protected $subjects      = null;
    protected $lang          = null;
    protected $url           = null;

    /**
     *  » Método para dar valor a las propiedades de la clase. Nos evita repetir este
     *    código en cada controlador que lo necesite
     *
     *  @param  ContainerInterface $container
     *
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->container    = $container;

        // Obtenemos la sesion y el request para poder trabajar en este controlador
        $request            = $container->get('request');
        $session            = $request->getSession();

        // Obtenemos el entorno en el que estamos y lo guardamos en sesión
        $session            ->set('environment', $container->get('kernel')->getEnvironment());

        // Obtenemos los parámetros del routing
        $paramRoutes        = $request->attributes->get('_route_params');

        $em             = $this->getDoctrine()->getManager();

        // Cargamos la lista de asignaturas
        $this->subjects       = $em->getRepository('ModelBundle:Subject')
                                ->findAll();

        // LANG
        $this->lang         = isset($paramRoutes["_locale"])?$paramRoutes["_locale"]:$this->container->getParameter("locale");

        // URL
        $urlRequest         = substr($this->getRequest()->getPathInfo(),1);
        $this->url          = $urlRequest?$urlRequest:$this->container->getParameter("locale");
    }
}
