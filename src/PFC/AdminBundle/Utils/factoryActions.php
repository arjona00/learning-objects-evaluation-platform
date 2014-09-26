<?php
namespace PFC\AdminBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use PFC\AdminBundle\Utils\exportXml;
use Symfony\Component\HttpFoundation\Response;

/**
* » Clase que factoriza varias funciones usadas en el proyecto.
*
*/
class factoryActions {
    /**
    * » Método para que muestra un listado de las asignaturas, y sus componentes, categorías y subcategorías
    *
    * @package PFC\AdminBundle\Utils;
    * @version 1.0
    *
    * @param    ObjectManager     $em     Doctine manager
    * @return   array       array con los parámetros para la renderización de la vista.
    */
    public function subjectComponentsList(ObjectManager $em)
    {
        $subjects   = $em->getRepository("ModelBundle:Subject")->findBy(array(), array('name' => 'ASC'));
        $pages      = $em->getRepository("ModelBundle:Page")->findBy(array(), array('slug' => 'ASC'));

        $subjectCategories = array();
        $categorySubcategories = array();
        $questions = array();

        foreach ($subjects as $subject) {
            $subjectCategories[$subject->getId()]    = $em->getRepository('ModelBundle:Category')
                                                            ->findBySubject($subject->getId());

            $questions['subject'][$subject->getId()] = 0;

            $questions['subject'][$subject->getId()] = count($em->getRepository('ModelBundle:Question')
                                                            ->findBySubject($subject->getId()));


            if (isset($subjectCategories[$subject->getId()])){
                foreach ($subjectCategories[$subject->getId()] as $category) {

                    $questions['category'][$category->getId()] = 0;

                    $questions['category'][$category->getId()] = count($em->getRepository('ModelBundle:Question')
                                                            ->findByCategory($category->getId()));

                    $categorySubcategories[$category->getId()]  = $em->getRepository('ModelBundle:Subcategory')
                                                            ->findByCategory($category->getId());

                    if (isset($categorySubcategories[$category->getId()])){
                        foreach ($categorySubcategories[$category->getId()] as $subcategory) {
                            $questions['subcategory'][$subcategory->getId()] = 0;

                            $questions['subcategory'][$subcategory->getId()] = count($em->getRepository('ModelBundle:Question')
                                                                            ->findBySubcategory($subcategory->getId()));
                        }
                    }

                }
            }
        }

        return  array(  'pages'                 => $pages,
                        'subjects'              => $subjects,
                        'subjectCategories'     => $subjectCategories,
                        'categorySubcategories' => $categorySubcategories,
                        'questions'             => $questions);
    }

    /**
    * » Método para que elimina un array de preguntas. Evita su repetición en los controladores.
    *
    * @package PFC\AdminBundle\Utils;
    * @version 1.0
    *
    * @param    ObjectManager     $em             Doctine manager.
    * @param    array       $questions      Array de preguntas a eliminar.
    * @return   void
    */
    public function deleteQuestions(ObjectManager $em, array $questions)
    {
        if(!empty($questions)){
            foreach($questions as $question){
                $answers = $em->getRepository('ModelBundle:Answer')->findByQuestion($question->getId());

                if(!empty($answers)){
                    foreach($answers as $answer){
                        $em->remove($answer);
                        $em->flush();
                    }
                }

                $em->remove($question);
                $em->flush();
            }
        }
    }

    /**
    * » Método para que muestra un listado de preguntas, del tipo seleccionado en el parámetro $selector
    *
    * @package PFC\AdminBundle\Utils;
    * @version 1.0
    *
    * @param    ObjectManager     $em            Doctine manager.
    * @param    subject     $subject       Objecto subject sobre el que se realiza la consulta.
    * @param    string      $selector      Indica el tipo objeto Subject, Category o Subcategory, para el que se exporta la lista.
    * @return   array()     Parametros necesarios para la renderización
    */
    public function testList(ObjectManager $em, $subject, $selector = '', $id = '')
    {
        $subjects   = $em->getRepository("ModelBundle:Subject")->findBy(array(), array('name' => 'ASC'));
        $pages      = $em->getRepository("ModelBundle:Page")->findBy(array(), array('slug' => 'ASC'));

        if ($selector == '') {
            $selector = 'Subject';
            $id = $subject->getId();
        }

        $object   = $em->getRepository("ModelBundle:" . $selector )->findOneById($id);

        $parameters = array('pages'                 => $pages,
                            'subjects'              => $subjects,
                            'subject'               => $subject,
                             strtolower($selector)  => $object);


        $questions = array();
        switch ($selector) {
            case 'Subject':
                $questions  = $em->getRepository("ModelBundle:Question")->findBySubject($id);
                break;
            case 'Category':
                $questions  = $em->getRepository("ModelBundle:Question")->findByCategory($id);
                break;
            case 'Subcategory':
                $questions  = $em->getRepository("ModelBundle:Question")->findBySubcategory($id);
                break;
        }
        $parameters['questions'] =  $questions;
        $questionAnswers        = array();

        foreach ($questions as $question){
            $answers    = $em->getRepository('ModelBundle:Answer')
                            ->findByQuestion($question->getId());
            $questionAnswers [$question->getId()] = $answers;
        }

        $parameters['questionAnswers'] =  $questionAnswers;

        return $parameters;
    }

    /**
    * » Devuelve al controlador la respuesta para la descarga de un fichero
    *
    * @package PFC\AdminBundle\Utils;
    * @version 1.0
    *
    * @param    string     $path           Directorio donde se encuentra el fichero a descargar en el navegador del cliente.
    * @param    string     $filename       Nombre del fichero a descargar en el navegador del cliente.
    * @return   response   objeto response compuesto con el fichero adjuntado.
    */
    public function downloadfileAction($path, $filename)
    {
        $path = $path . $filename;

        $content = file_get_contents($path);

        $response = new Response();

        $response->headers->set('Content-Type', 'text/xml');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);

        $response->setContent($content);
        return $response;
    }

    /**
    * » Método que gestiona la exportación de un cuestionario a un fichero.
    *
    * @package PFC\AdminBundle\Utils;
    * @version 1.0
    *
    * @param    ObjectManager    $em                 Doctine manager.
    * @param    string     $path               Directorio donde se encuentra el fichero a descargar en el navegador del cliente.
    * @param    string     $selector           Indica el tipo objeto Subject, Category o Subcategory, para el que se exporta la lista.
    * @param    string     $level              Parámetro opcional con el nivel de las preguntas a exportar. 0 implica cualquier nivel
    * @param    string     $numQuestions       Parámetro opcional con el número de preguntas máximo a exportar.
    * @return   render     Renderización de un objeto response con el fichero adjunto. Provoca la descarga en el navegador del cliente
    */
    public function exportTest(ObjectManager $em, $path, $selector = '', $id = '', $level = 0, $numQuestions = '' )
    {

        $questions = array();

        $questions  = $em->getRepository("ModelBundle:Question")->findByTypeIdLevelCount($selector, $id, $level, $numQuestions);

        $quizExport = new exportXml();
        $quizExport->loadFromBd($em, $questions);
        $quizExport->setPathDir($path);

        if ($quizExport->saveFile()){
            return  $this->downloadfileAction($quizExport->getPathDir(), $quizExport->getFilename());
        }
    }
}