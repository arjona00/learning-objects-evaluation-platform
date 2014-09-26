<?php

namespace PFC\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use PFC\AdminBundle\Utils\importXml;
use PFC\AdminBundle\Utils\importScorm;
use PFC\AdminBundle\Utils\importQTI;
use PFC\AdminBundle\Utils\importGift;

/**
* Clase controlador que implementa las operaciones de importación de preguntas
*
* @package PFC\AdminBundle\Controller;
* @version 1.0
*
*/
class QuestionController extends Controller
{
    /**
    *  » Método para que muestra el formulario de importación de preguntas
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   integer    $subcategory_id   id de la subcategoría
    *
    * @return  Render      Devuelve la vista AdminBundle:Question:index.html.twig
    */
    public function indexAction($subcategory_id)
    {
        $em             = $this->getDoctrine()->getManager();
        $subjects       = $em->getRepository("ModelBundle:Subject")->findBy(array(), array('name' => 'ASC'));
        $pages          = $em->getRepository("ModelBundle:Page")->findBy(array(), array('slug' => 'ASC'));
        $subcategory    = $em->getRepository("ModelBundle:Subcategory")->findOneById($subcategory_id);

        $result = '';

        return $this->render('AdminBundle:Question:index.html.twig',
                                                    array(  'pages'             => $pages,
                                                            'subjects'          => $subjects,
                                                            'subcategory'       => $subcategory,
                                                            'result'            => $result));
    }

    /**
     * Método para la importación de Questions. Landing page para la importación. Selecciona el método dependiendo del tipo de importación a realizar.
     *
     * @package PFC\AdminBundle\Controller;
     * @version 1.0
     *
     * @param    integer   $subcategory_id   Identificador de la subcategoría a la que pertenecerá las Quiestions
     *
     * @return   Render    Devuelve la vista AdminBundle:Question:index.html.twig con el resultado de la operación de importación
     *
     */
    public function uploadAction($subcategory_id)
    {
        $em             = $this->getDoctrine()->getManager();
        $subjects       = $em->getRepository("ModelBundle:Subject")->findBy(array(), array('name' => 'ASC'));
        $pages          = $em->getRepository("ModelBundle:Page")->findBy(array(), array('slug' => 'ASC'));
        $subcategory    = $em->getRepository("ModelBundle:Subcategory")->findOneById($subcategory_id);

        $request        = $this->container->get('request');

        $pathDir = $this->get('kernel')->getRootDir() . '/../web/uploads/imports/';

        $importType = $request->get('import-type');

        switch ($importType) {
            case 'gift':
                $file = new importGift();
                break;

            case 'qti':
                $file = new importQTI();
                break;

            case 'scorm':
                $file = new importQTI(); //Solo importamos SCORM si cumple la especificación IMS CP
                break;

            case 'xml':
                $file = new importXml();
                break;

            default:
                // error
                break;
        }

        $file->setPathDir($pathDir);

        $result = $this->getUpload($file, $em, $subcategory);

        return $this->render('AdminBundle:Question:index.html.twig',
                                                    array(  'pages'             => $pages,
                                                            'subjects'          => $subjects,
                                                            'subcategory'       => $subcategory,
                                                            'result'            => $result));
    }

    /**
    *  » Método usado por el action upload para la importación de las Questions
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   object   $file          Objeto que representa al tipo de importación (XML, QTI, etc.)
    * @param   object   $em            Entity Managers, necesario para manejar bases de datos con Doctrine
    * @param   object   $subcategory   Objeto que contiene una entidad Subcategoría.
    *
    * @return  string   $result        Devuleve un mensaje de salida con el resultado de la operación de imporación
    *
    */
    private function getUpload ($file, $em, $subcategory)
    {
        $fs = new Filesystem();
        switch ($file->importFile()) {
            case '-1': // Importación correcta
                $result = 'Fichero importado correctamente.';
                $file->saveFileToBd($em, $subcategory);
                break;

            case '-2': // Importación correcta (resultado parcial, algunas preguntas se consideraron como incorrectas)
                $result = 'Fichero importado parcialmente. Algunas de las preguntas fueron desechadas devido a fallos en su formato o incompatibilidades.';
                $file->saveFileToBd($em, $subcategory);
                break;

            case '0': // Importación incorrecta, error
                $result = 'Fichero no tine la extensión requerida.';
                break;

            case '1': // Importación incorrecta, error
                $result = 'Error en la serialización del archivo. No contiene el formato requerido.';
                break;

            case '2': // Importación incorrecta, error
                $result = 'El fichero no puede ser desempaquetado. El formato no es el requerido.';
                break;

            case '3': // Importación incorrecta, error
                $result = 'El formato del paquete no es correcto. Faltan alguno de los archivos necesarios';
                break;

            case '4': // Importación incorrecta, error
                $result = 'El formato del/os fichero/s no es el compatible o contiene/n errore/s.';
                break;

            default: // Importación incorrecta, error
                $result = 'Error indeterminado.';
                break;
        }
        $fs->remove($file->pathDir); //Borra el directorio temporal de importación de la aplicación
        return $result;
    }
}
