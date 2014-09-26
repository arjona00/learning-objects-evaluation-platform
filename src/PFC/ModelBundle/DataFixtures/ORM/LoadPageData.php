<?php

namespace PFC\ModelBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Gedmo\Sluggable\Util\Urlizer;

use PFC\ModelBundle\Entity\Page;
use PFC\ModelBundle\Entity\PageUrl;


/**
*  » Data Fixtures que crea las páginas y las guarda en la base de datos
*
*/
class LoadPageData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private     $container;
    protected   $priority = 1;


    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
    *  » Método que establece el orden de la carga todos los datos de prueba y de las páginas.
    *
    * @package PFC\ModelBundle\DataFixtures\ORM;
    * @version 1.0
    *
    * @param    ObjectManager    $em                 Doctine manager.
    * @return   void
    */
    public function load(ObjectManager $manager)
    {

        $this->updateDefaultPages($manager);
        $this->updateSubjectPages($manager);
        $this->updateCategoryPages($manager);
    }

    /**
    *  » Inserta las páginas por defecto.
    *
    * @package PFC\ModelBundle\DataFixtures\ORM;
    * @version 1.0
    *
    * @param    ObjectManager    $em                 Doctine manager.
    * @return   void
    */
    private function updateDefaultPages($manager)
    {
        $urlizer            = new Urlizer();


        //Lista de páginas por defecto en el menú
        $arrayDefaultPages  = array (
                                        "Index"       => "index"
                                    );

        $arrayDataDefaultPages  = array (
                                        "Index"                           => array("type" => 0, "priority" => 1)
                                    );

        if(!empty($arrayDefaultPages)){
            foreach($arrayDefaultPages as $url => $slug){

                // Insertamos la página
                $page       = new Page();
                $page       ->setTitle($url);
                $page       ->setSlug($slug);
                $page       ->setActive(true);
                $page       ->setType($arrayDataDefaultPages[$url]['type']);
                $page       ->setPriority($arrayDataDefaultPages[$url]['priority']);
                $manager    ->persist($page);
                $manager    ->flush();
                $page       = ObjectIdentity::fromDomainObject($page);


                // Obtenemos el identificador de la página
                $page       = $manager->getRepository('ModelBundle:Page')->find($page->getIdentifier());

                // Insertamos la Url de la página en español
                $pageUrl    = new PageUrl();
                $pageUrl    ->setLang("es");
                $pageUrl    ->setPage($page);
                $pageUrl    ->setUrl( ($url == 'Index')?"/":$urlizer->urlize($url));
                $manager    ->persist($pageUrl);

                $manager    ->flush();
            }
        }
    }

    /**
    *  » Inserta las páginas de cada asignatura cargada de prueba
    *
    * @package PFC\ModelBundle\DataFixtures\ORM;
    * @version 1.0
    *
    * @param    ObjectManager    $em                 Doctine manager.
    * @return   void
    */
    private function updateSubjectPages($manager)
    {
        $urlizer            = new Urlizer();


        //Recopilamos todas las asignaturas
        $repo           = $manager->getRepository('ModelBundle:Subject');
        $routes         = $repo->findAll();

        if(!empty($routes)){

            foreach($routes as $subject){

                // Insertamos la página de la asignatura
                $page       = new Page();
                $page       ->setTitle($subject->getName());
                $page       ->setActive(true);
                $page       ->setType(1);
                $page       ->setPriority($this->priority++);
                $manager    ->persist($page);
                $manager    ->flush();
                $page       = ObjectIdentity::fromDomainObject($page);

                // Obtenemos el identificador de la página
                $page       = $manager
                        ->getRepository('ModelBundle:Page')
                        ->find($page->getIdentifier());

                // Actualizamos la asignatura con la página a la que pertenece
                $subject->setPage($page);
                $manager->persist($subject);
                $manager->flush();

                // Insertamos la Url de la página
                $pageUrl    = new PageUrl();
                $pageUrl    ->setLang("es");
                $pageUrl    ->setPage($page);
                //$pageUrl    ->setUrl( ($url)?$urlizer->urlize($subject->slug):"es");
                $pageUrl    ->setUrl( $urlizer->urlize($subject->getName()));
                $manager    ->persist($pageUrl);

                $manager    ->flush();
            }

        }
    }

    /**
    *  » Inserta las páginas de cada categoría de las asignaturas
    *
    * @package PFC\ModelBundle\DataFixtures\ORM;
    * @version 1.0
    *
    * @param    ObjectManager    $em                 Doctine manager.
    * @return   void
    */
    private function updateCategoryPages($manager)
    {
        $urlizer            = new Urlizer();


        //Recopilamos todas las asignaturas
        $repo           = $manager->getRepository('ModelBundle:Category');
        $routes         = $repo->findAll();

        if(!empty($routes)){

            $repoSubject           = $manager->getRepository('ModelBundle:Subject');

            foreach($routes as $category){

                // Insertamos la página de la asignatura
                $page       = new Page();
                $page       ->setTitle($category->getName() . ': ' . $category->getTitle());
                $page       ->setActive(true);
                $page       ->setType(2);
                $page       ->setPriority($this->priority++);
                $manager    ->persist($page);
                $manager    ->flush();
                $page       = ObjectIdentity::fromDomainObject($page);

                // Obtenemos el identificador de la página
                $page       = $manager
                        ->getRepository('ModelBundle:Page')
                        ->find($page->getIdentifier());

                // Actualizamos la asignatura con la página a la que pertenece
                $category->setPage($page);
                $manager->persist($category);
                $manager->flush();

                // Insertamos la Url de la página
                $pageUrl    = new PageUrl();
                $pageUrl    ->setLang("es");
                $pageUrl    ->setPage($page);

                $subject    = $repoSubject->find($category->getSubject()->getId());

                $pageUrl    ->setUrl(
                            $urlizer->urlize($subject->getName()) . "/" .
                            $urlizer->urlize($category->getName()) . "-" .
                            $urlizer->urlize($category->getTitle())
                                    );
                $manager    ->persist($pageUrl);

                $manager    ->flush();
            }

        }
    }

    public function getOrder()
    {
        return 50;
    }
}