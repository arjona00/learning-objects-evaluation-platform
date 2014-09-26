<?php

namespace PFC\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use PFC\ModelBundle\Entity\Category;
use PFC\ModelBundle\Entity\Subcategory;
use PFC\ModelBundle\Entity\Subject;
use PFC\AdminBundle\Utils\factoryActions;

/**
* Clase controlador que implementa el CRUD de las subcategorías
*
* @package PFC\AdminBundle\Controller;
* @version 1.0
*
*/
class SubcategoryController extends Controller
{

    /**
    * » Método para que muestra un listado de las subcategorías
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Null 
    * @return  Render      Devuelve la vista AdminBundle:Subject:list.html.twig, que se encuentra factorizada en la clase factoryActions->subjectComponentsList
    */
    public function listAction()
    {
        $fa     = new factoryActions();
        $em     = $this->getDoctrine()->getManager();

        return $this->render('AdminBundle:Subject:list.html.twig', $fa->subjectComponentsList($em));
    }

    /**
    *  » Método para que muestra un listado de cuestiones de la subcategoría
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string       $subject           slug único de la asignatura
    * @param   integer      $subcategory_id    id de la subcategoría seleccionada
    * @return  Render       Devuelve la vista Subject:subcategory-test-list.html.twig, que se encuentra factorizada en la clase factoryActions->testList
    *
    */
    public function testListAction($subject, $subcategory_id)
    {
        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();

        $subject   = $em->getRepository("ModelBundle:Subject")->findOneBySlug($subject);

        return $this->render('AdminBundle:Subject:subcategory-test-list.html.twig', $fa->testList($em, $subject, 'Subcategory', $subcategory_id));
    }

    /**
    *  » Actualiza los datos de las subcategorías
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string       $field              Campo de la subcategoría a actualizar
    * @return  Response     Devuelve el control a la vista actual
    *
    */
    public function updateAction($field)
    {
        $request     = $this->container->get('request');
        $subcategory_id = $request->get('pk');

        if(isset($subcategory_id)){

            // Obtenemos el objeto a actualizar
            $em         = $this->getDoctrine()->getManager();
            $subcategory   = $em->getRepository('ModelBundle:Subcategory')->findOneById($subcategory_id);
            $value      = $request->get('value');

            switch ($field) {
                case 'name':
                        $subcategory->setName($value);
                    break;
                case 'title':
                        $subcategory->setTitle($value);
                    break;
                case 'description':
                        $subcategory->setDescription($value);
                    break;
                default:
                    # code...
                    break;
            }

            // Actualizamos los datos de la asignatura
            $em->persist($subcategory);
            $em->flush();
        }

        return new Response();
    }

    /**
    *  » Crea una subcategoría, asociándole la asignatura y categoría actual, y guardando solo el nombre introducido
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   null
    * 
    * @return  Function  $this->listAction()
    */
    public function createAction()
    {
        $request     = $this->container->get('request');
        $category_id = $request->get('pk');

        if(isset($category_id)){

            // Creamos el nuevo objeto
            $em         = $this->getDoctrine()->getManager();

            $subcategory    = new Subcategory();
            $value      = $request->get('value');

            $category = new Category();
            $category = $em->getRepository('ModelBundle:Category')->findOneById($category_id);

            $subcategory->setSubject($category->getSubject());
            $subcategory->setCategory($category);
            $subcategory->setName($value);
            $subcategory->setTitle('Título');
            $subcategory->setDescription('Descripción');

            // Actualizamos los datos de la categoría
            $em->persist($subcategory);
            $em->flush();
        }

        return $this->listAction();
    }

    /**
    *  » Elimina la subcategoría seleccionada, y todas sus cuestiones asociadas
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $subcategory_id    Id de la subcategoría a eliminar
    * 
    * @return  Function  $this->listAction()
    */
    public function deleteAction($subcategory_id)
    {
        if(isset($subcategory_id)){

            // Obtenemos el objeto a actualizar
            $em          = $this->getDoctrine()->getManager();
            $subcategory    = $em->getRepository('ModelBundle:Subcategory')->findOneById($subcategory_id);

            //Eliminamos las preguntas de la subcategoría
            $questions = $em->getRepository('ModelBundle:Question')->findBySubcategory($subcategory_id);
            if(!empty($questions)){
                $fa = new factoryActions();
                $fa->deleteQuestions($em, $questions);
            }

            // Eliminamos la subcategoría
            $em->remove($subcategory);
            $em->flush();
        }

        return $this->listAction();
    }

    /**
    *  » Elimina una pregunta especifica seleccionada
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   String   $subject            Slug de la asignatura seleccionada
    * @param   Integer  $subcategory_id     Id de la subcategoría seleccionada
    * @param   Integer  $question_id        Id de la pregunta a eliminar
    * 
    * @return  Function  $this->testListAction($subject, $subcategory_id)
    */
    public function deleteTestAction($subject, $subcategory_id, $question_id)
    {
        $em         = $this->getDoctrine()->getManager();
        $questions = $em->getRepository('ModelBundle:Question')->findById($question_id);
        if(!empty($questions)){
            $fa = new factoryActions();
            $fa->deleteQuestions($em, $questions);
        }

        return $this->testListAction($subject, $subcategory_id);
    }

    /**
    *  » Método para que exporta a un fichero moodelXml, una batería de preguntas pertenecientes a la subcategoría pasada como parámetro
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $subcategory_id    Id de la subcategoría seleccionada
    * 
    * @return  Function  $fa->exportTest($em, $path, 'Subcategory', $subcategory_id)
    */
    public function exportAction($subcategory_id)
    {

        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();
        $path   = $this->get('kernel')->getRootDir() . '/../web/downloads/';

        $request     = $this->container->get('request');
        $level = $request->get('level'); 
        $numQuestions = $request->get('numQuestions'); 

        return ($fa->exportTest($em, $path, 'Subcategory', $subcategory_id, $level, $numQuestions));

    }
}
