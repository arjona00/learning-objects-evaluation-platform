<?php

namespace PFC\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use PFC\ModelBundle\Entity\Category;
use PFC\ModelBundle\Entity\Subject;
use PFC\AdminBundle\Utils\factoryActions;

/**
 * Clase controlador que implementa el CRUD de las categorías
 *
 * @package PFC\AdminBundle\Controller;
 * @version 1.0
 *
 */
class CategoryController extends Controller
{

    /**
    *  » Método para que muestra un listado de las categorías
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   void
    * @return  Render Devuelve la vista AdminBundle:Subject:list.html.twig.
    *
    */
    public function listAction()
    {
        $fa     = new factoryActions();
        $em     = $this->getDoctrine()->getManager();

        return $this->render('AdminBundle:Subject:list.html.twig', $fa->subjectComponentsList($em));
    }

    /**
    *  » Método para que muestra un listado de preguntas de una categoría
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string    $subject        slug único de la asignatura
    * @param   integer   $category_id    id de la categoría seleccionada
    *
    * @return  Render Devuelve la vista index.html.twig
    *
    */
    public function testListAction($subject, $category_id)
    {
        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();

        $subject   = $em->getRepository("ModelBundle:Subject")->findOneBySlug($subject);

        return $this->render('AdminBundle:Subject:category-test-list.html.twig', $fa->testList($em, $subject, 'Category', $category_id));
    }

    /**
    *  » Actualiza los campos de la categoría, guardándolos en la bd
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string   $field         Campo a actualizar de la categoría actual
    *
    * @return  Response Vuelve a la vista actual
    */
    public function updateAction($field)
    {
        $request     = $this->container->get('request');
        $category_id = $request->get('pk');

        if(isset($category_id)){

            // Obtenemos el objeto a actualizar
            $em         = $this->getDoctrine()->getManager();
            $category   = $em->getRepository('ModelBundle:Category')->findOneById($category_id);
            $value      = $request->get('value');

            switch ($field) {
                case 'name':
                        $category->setName($value);
                    break;
                case 'title':
                        $category->setTitle($value);
                    break;
                case 'description':
                        $category->setDescription($value);
                    break;
                default:
                    # code...
                    break;
            }

            // Actualizamos los datos de la categoría
            $em->persist($category);
            $em->flush();
        }

        return new Response();
    }

    /**
    *  » Crea una categoría, asociándole la asignatura actual, y guardando solo el nombre introducido
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
        $subject_id = $request->get('pk');

        if(isset($subject_id)){

            // Creamos el nuevo objeto
            $em         = $this->getDoctrine()->getManager();

            $category    = new Category();
            $value      = $request->get('value');

            $subject = new Subject();
            $subject   = $em->getRepository('ModelBundle:Subject')->findOneById($subject_id);

            $category->setSubject($subject);
            $category->setName($value);
            $category->setTitle('Título');
            $category->setDescription('Descripción');

            // Actualizamos los datos de la categoría
            $em->persist($category);
            $em->flush();
        }

        return $this->listAction();
    }

    /**
    *  » Elimina la categoría seleccionada, todas las subcategorías relacionadas y sus cuestiones
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $category_id    Id de la categoría a eliminar
    *
    * @return  Function  $this->listAction()
    */
    public function deleteAction($category_id)
    {
        if(isset($category_id)){

            // Obtenemos el objeto a actualizar
            $em          = $this->getDoctrine()->getManager();
            $category    = $em->getRepository('ModelBundle:Category')->findOneById($category_id);

            //Eliminamos la preguntas de la categoría
            $questions = $em->getRepository('ModelBundle:Question')->findByCategory($category_id);
            if(!empty($questions)){
                $fa = new factoryActions();
                $fa->deleteQuestions($em, $questions);
            }

            //Eliminamos las subcategorías
            $subcategories   = $em->getRepository('ModelBundle:Subcategory')->findByCategory($category_id);

            if(!empty($subcategories)){
                foreach($subcategories as $subcategory){
                    $em->remove($subcategory);
                }
            }

            // Eliminamos la categoría
            $em->remove($category);
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
    * @param   String  $subject         Slug de la asignatura seleccionada
    * @param   Integer  $category_id    Id de la categoría seleccionada
    * @param   Integer  $question_id    Id de la pregunta a eliminar
    *
    * @return  Function  $this->testListAction($subject, $category_id)
    */
    public function deleteTestAction($subject, $category_id, $question_id)
    {
        $em         = $this->getDoctrine()->getManager();
        $questions = $em->getRepository('ModelBundle:Question')->findById($question_id);
        if(!empty($questions)){
            $fa = new factoryActions();
            $fa->deleteQuestions($em, $questions);
        }

        return $this->testListAction($subject, $category_id);
    }

    /**
    *  » Método para que exporta a un fichero moodelXml, una batería de preguntas pertenecientes a la categoría pasada como parámetro
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $category_id    Id de la categoría seleccionada
    *
    * @return  Function  $fa->exportTest($em, $path, 'Category', $category_id)
    */
    public function exportAction($category_id)
    {

        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();
        $path   = $this->get('kernel')->getRootDir() . '/../web/downloads/';

        $request     = $this->container->get('request');
        $level = $request->get('level');
        $numQuestions = $request->get('numQuestions');

        return ($fa->exportTest($em, $path, 'Category', $category_id, $level, $numQuestions));

    }
}
