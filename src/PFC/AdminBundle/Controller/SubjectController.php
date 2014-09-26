<?php

namespace PFC\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use PFC\ModelBundle\Entity\Subject;
use PFC\AdminBundle\Utils\factoryActions;

/**
* Clase controlador que implementa el CRUD de las asignaturas
*
* @package PFC\AdminBundle\Controller;
* @version 1.0
*
*/
class SubjectController extends Controller
{
    /**
    * » Método para que muestra un listado de las asignaturas
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

        //$user = $this->getUser();
        //ld($user->getUsername());

        return $this->render('AdminBundle:Subject:list.html.twig', $fa->subjectComponentsList($em));
    }

    /**
    *  » Método para que muestra un listado de cuestiones de la asignatura
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string       $subject           slug único de la asignatura
    * @return  Render       Devuelve la vista Subject:subcategory-test-list.html.twig, que se encuentra factorizada en la clase factoryActions->testList
    *
    */
    public function testListAction($subject)
    {
        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();

        $subject   = $em->getRepository("ModelBundle:Subject")->findOneBySlug($subject);

        return $this->render('AdminBundle:Subject:subject-test-list.html.twig', $fa->testList($em,  $subject));
    }

    /**
    *  » Actualiza los datos de las asignaturas
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   string       $field              Campo de la asignatura a actualizar
    * @return  Response     Devuelve el control a la vista actual
    *
    */
    public function updateAction($field)
    {
        $request     = $this->container->get('request');
        $subject_id = $request->get('pk');

        if(isset($subject_id)){

            // Obtenemos el objeto a actualizar
            $em         = $this->getDoctrine()->getManager();
            $subject   = $em->getRepository('ModelBundle:Subject')->findOneById($subject_id);
            $value      = $request->get('value');

            switch ($field) {
                case 'name':
                        $subject->setName(strtoupper($value));
                    break;
                case 'description':
                        $subject->setDescription($value);
                    break;
                default:
                    # code...
                    break;
            }

            // Actualizamos los datos de la asignatura
            $em->persist($subject);
            $em->flush();
        }

        return new Response();
    }

    /**
    *  » Crea una asignatura vacía y guardando solo el titulo introducido
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

        // Creamos el nuevo objeto
        $em         = $this->getDoctrine()->getManager();

        $subject    = new Subject();
        $value      = $request->get('value');

        //Pueden existir dos asignaturas con el mismo nombre, ya que están slugeadas
        $subject->setName(strtoupper($value));
        $subject->setDescription('Descripción');

        // Actualizamos los datos de la asignatura
        $em->persist($subject);
        $em->flush();

        return $this->listAction();
    }

    /**
    *  » Elimina la asignatura seleccionada, las categorías, subcategorías y preguntas asociadas
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $subject_id    Id de la asignatura a eliminar
    * 
    * @return  Function  $this->listAction()
    */
    public function deleteAction($subject_id)
    {
        if(isset($subject_id)){

            // Obtenemos el objeto a actualizar
            $em         = $this->getDoctrine()->getManager();
            $subject    = $em->getRepository('ModelBundle:Subject')->findOneById($subject_id);

            //Eliminamos las preguntas y respuestas
            $questions = $em->getRepository('ModelBundle:Question')->findBySubject($subject->getId());
            if(!empty($questions)){
                $fa = new factoryActions();
                $fa->deleteQuestions($em, $questions);
            }
            //Eliminamos las subcategorías
            $subcategories   = $em->getRepository('ModelBundle:Subcategory')->findBySubject($subject->getId());

            if(!empty($subcategories)){
                foreach($subcategories as $subcategory){
                    $em->remove($subcategory);
                }
            }

            //Eliminamos las categorías
            $categories   = $em->getRepository('ModelBundle:Category')->findBySubject($subject_id);

            if(!empty($categories)){
                foreach($categories as $category){
                    $em->remove($category);
                }
            }

            // Eliminamos la asignatura
            $em->remove($subject);
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
    * @param   Integer  $question_id        Id de la pregunta a eliminar
    * 
    * @return  Function  $this->testListAction($subject, $subcategory_id)
    */
    public function deleteTestAction($subject, $question_id)
    {
        $em         = $this->getDoctrine()->getManager();
        $questions = $em->getRepository('ModelBundle:Question')->findById($question_id);
        if(!empty($questions)){
            $fa = new factoryActions();
            $fa->deleteQuestions($em, $questions);
        }

        return $this->testListAction($subject);
    }

    /**
    *  » Método para que exporta a un fichero moodelXml, una batería de preguntas pertenecientes a la asignatura pasada como parámetro
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   Integer  $subject_id    Id de la asignatura seleccionada
    * 
    * @return  Function  $fa->exportTest($em, $path, 'Subject', $subject_id)
    */
    public function exportAction($subject_id)
    {

        $em     = $this->getDoctrine()->getManager();
        $fa     = new factoryActions();
        $path   = $this->get('kernel')->getRootDir() . '/../web/downloads/';

        $request     = $this->container->get('request');
        $level = $request->get('level'); 
        $numQuestions = $request->get('numQuestions'); 

        return ($fa->exportTest($em, $path, 'Subject', $subject_id, $level, $numQuestions));
    }


}
