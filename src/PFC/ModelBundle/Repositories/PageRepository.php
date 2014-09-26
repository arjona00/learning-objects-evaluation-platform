<?php

/**
*  » Repositorio PageRepository
*    ┌─────────────────────────────────────────────────────────────────────────┐
*      - @version 1.0 @date 2013-08-20
*    └─────────────────────────────────────────────────────────────────────────┘
*/

namespace PFC\ModelBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class PageRepository extends EntityRepository
{

    /**
     *  » Obtiene la estructura de páginas sobre la página actual (breadcrumbs)
     *  @param     string   $lang         Idioma
     *  @param     Page     $page         Página
     *  @return    array    $breadCrumb   Array de páginas que forman el breadcrumb
     */
    public function getBreadCrumb($page, $lang)
    {
        $breadCrumb         = array();
        $em                 = $this ->getEntityManager();
        $repoPageUrl        = $em->getRepository('ModelBundle:PageUrl');

        // Mientras no estemos en la página de inicio vamos añadiendo la página
        $i = 0;
        while($page->getSlug() != "Index")
        {
            $breadCrumb[$i]['anchor']   = $page->getTitle();
            $pageUrl                    = $repoPageUrl->findOneBy(array("page" => $page->getId(), "lang" => $lang));
            $breadCrumb[$i]['link']     = $pageUrl->getUrl();

            $page                       = $page->getParent();
            $i++;
        }

        // Añadimos la página de inicio (Index)
        $breadCrumb[$i]['anchor']       = $page->getTitle();
        $pageUrl                        = $repoPageUrl->findOneBy(array("page" => $page->getId(), "lang" => $lang));
        $breadCrumb[$i]['link']         = "";

        return array_reverse($breadCrumb);
    }
}