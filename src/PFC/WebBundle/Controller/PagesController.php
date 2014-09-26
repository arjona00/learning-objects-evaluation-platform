<?php

namespace PFC\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;

/**
*  » Controlador para la renderización de páginas y la ejecución de los cuestionarios para el usuario.
**/
class PagesController  extends BaseController
{
    public function indexAction()
    {
        return $this->render('WebBundle:Pages:index.html.twig',
                                                            array('subjects'    =>    $this->subjects));
    }

    /**
    * » Método que realiza las tareas del controlador después de seleccionar una aisgnatura.
    *
    * @package PFC\WebBundle\Controller;
    * @version 1.0
    *
    * @param   string     $subjectSlug    slug de la subcategoría
    *
    * @return  Render     Devuelve la vista WebBundle:Pages:subject.html.twig que muestra la asignatura, sus categorías y sus subcategorías, con los cuestionarios a realizar.
    */
    public function subjectAction($subjectSlug)
    {

    	$em             = $this->getDoctrine()->getManager();

        $subject       = $em->getRepository('ModelBundle:Subject')
                                ->findOneBySlug($subjectSlug);

        $subjectCategories    = $em->getRepository('ModelBundle:Category')
                                      ->findBySubject($subject->getId());

        $categorySubcategories = array();

        if (isset($subjectCategories)){
            foreach ($subjectCategories as $category){

                $subcategories = $em->getRepository('ModelBundle:Subcategory')
                                          ->findByCategory($category->getId());

                $categorySubcategories [$category->getId()] = $subcategories;

            }
        }
        return $this->render('WebBundle:Pages:subject.html.twig',
        														array( 'subject'               =>    $subject,
                                                                       'subjects'              =>    $this->subjects,
                                                                       'subjectCategories'     =>    $subjectCategories,
                                                                       'categorySubcategories' =>    $categorySubcategories));
    }


    /**
    * » Renderización de la página con la selección de nivel del cuestionario.
    *
    * @package PFC\WebBundle\Controller;
    * @version 1.0
    *
    * @param   string     $subjectSlug      slug de la asignatura
    * @param   string     $categorySlug     opcional, slug de la categoría
    * @param   string     $subCategorySlug  opcional, slug de la subcategoría
    *
    * @return  Render     Devuelve la vista WebBundle:Pages:level-selection.html.twig
    */
     public function levelSelectionAction($subjectSlug, $categorySlug = '', $subCategorySlug = '')
    {

        $em             = $this->getDoctrine()->getManager();

        $subject        = $em->getRepository('ModelBundle:Subject')
                            ->findOneBySlug($subjectSlug);

        $conditionSql   = '';
        $condition_id   = '';
        $conditionSlug  = '';
        $conditionIndex = '';

        if ($subCategorySlug != ''){

            $subcategory       = $em->getRepository('ModelBundle:Subcategory')
                                ->findOneBySlug($subCategorySlug);
            $conditionSql   = 'q.subcategory = :subcategory AND ';
            $condition_id   = $subcategory->getId();
            $conditionSlug  = $subcategory->getSlug();
            $conditionIndex = 'subcategory';

        }elseif ($categorySlug != ''){

            $category       = $em->getRepository('ModelBundle:Category')
                                ->findOneBySlug($categorySlug);
            $conditionSql   = 'q.category = :category AND ';
            $condition_id   = $category->getId();
            $conditionSlug  = $category->getSlug();
            $conditionIndex = 'category';
        }


        $levels = array();

        for ($i = 0; $i <= 9; $i++){

            $arrayParameters = array();
            $arrayParameters['subject']  =  $subject->getId();
            $arrayParameters['minlevel'] =  $i;
            $arrayParameters['maxlevel'] =  $i + 1;

            if ($i == 0){
                $conditionLevel = ' q.level >= :minlevel AND q.level <= :maxlevel '; //[0..1]
            }else{
                $conditionLevel = ' q.level > :minlevel AND q.level <= :maxlevel ';  //(1..2](2..3]...(9..10]
            }

            if ($conditionSlug != ''){
                $arrayParameters[$conditionIndex] =  $condition_id;
            }

            //var_dump($arrayParameters);

            $query = $em->createQuery(
                'SELECT q
                FROM ModelBundle:Question q
                WHERE q.subject = :subject AND ' . $conditionSql . $conditionLevel . ' ORDER BY q.level ASC'
            )->setParameters($arrayParameters);
            //ldd($query);
            $questions = $query->getResult();
            $levels[$i] = count($questions);
            //ld($i . ' ' . count($questions));
        }
        return $this->render('WebBundle:Pages:level-selection-form.html.twig',
                                                                array( 'subject'            =>    $subject,
                                                                       'subjects'           =>    $this->subjects,
                                                                       'categorySlug'       =>    $categorySlug,
                                                                       'subCategorySlug'    =>    $subCategorySlug,
                                                                       'levels'             =>    $levels));
    }


    /**
    * » Renderización de la página con la lista de preguntas del nivel, asignatura, categoría o subcategoría seleccionadas
    *
    * @package PFC\WebBundle\Controller;
    * @version 1.0
    *
    * @param   string     $subjectSlug      slug de la asignatura
    * @param   integer    $level            nivel del cuestionario. 0 indica aleatorio de cualquier nivel
    * @param   string     $categorySlug     opcional, slug de la categoría
    * @param   string     $subCategorySlug  opcional, slug de la subcategoría
    *
    * @return  Render     Devuelve la vista WebBundle:Pages:test.html.twig
    */
    public function testAction(Request $request, $subjectSlug, $categorySlug = '', $subCategorySlug = '' ) //$level = 0, preguntas de cualquier nivel.
    {
        $testRequest = $request->request;

        //var_dump($testRequest);

        $level = $testRequest->get('level');

        $maxQuestions = $testRequest->get('numMaxQuestions');

        if (!is_numeric($maxQuestions)) $maxQuestions = 10;

        //ld($subjectSlug . ' - ' . $categorySlug . ' - ' . $subCategorySlug . ': Level ' . $level . ' : maxQuestions ' . $maxQuestions);

        $em             = $this->getDoctrine()->getManager();

        $subject        = $em->getRepository('ModelBundle:Subject')
                            ->findOneBySlug($subjectSlug);

        $conditionSql   = '';
        $condition_id   = '';
        $conditionSlug  = '';
        $conditionIndex = '';
        $conditionLevel = '';

        if ($subCategorySlug != ''){

            $subcategory       = $em->getRepository('ModelBundle:Subcategory')
                                ->findOneBySlug($subCategorySlug);
            $conditionSql   = 'AND q.subcategory = :subcategory ';
            $condition_id   = $subcategory->getId();
            $conditionSlug  = $subcategory->getSlug();
            $conditionIndex = 'subcategory';

        }elseif ($categorySlug != ''){

            $category       = $em->getRepository('ModelBundle:Category')
                                ->findOneBySlug($categorySlug);
            $conditionSql   = 'AND  q.category = :category ';
            $condition_id   = $category->getId();
            $conditionSlug  = $category->getSlug();
            $conditionIndex = 'category';
        }

        //Parametros para la consulta
        $arrayParameters = array();
        $arrayParameters['subject']  =  $subject->getId();

        if ( $level != 0){
            $arrayParameters['minlevel'] =  $level - 1;
            $arrayParameters['maxlevel'] =  $level;
            if ($level == 1){
                $conditionLevel   = ' AND q.level >= :minlevel AND q.level <= :maxlevel ';
            }else{
                $conditionLevel   = ' AND q.level > :minlevel AND q.level <= :maxlevel ';
            }
        }

        if ($conditionSlug != ''){
            $arrayParameters[$conditionIndex] =  $condition_id;
        }
        //var_dump($conditionSql);

        $query = $em->createQuery(
            'SELECT q
            FROM ModelBundle:Question q
            WHERE q.subject = :subject ' . $conditionSql . $conditionLevel
        )->setParameters($arrayParameters);

        $questions = $query->getResult();

        //Barajamos las cuestiones y seleccionamos a lo sumo 10
        srand (time());
        shuffle ($questions);

        $questionAnswers        = array();
        $limitQuestions         = array();
        $i = 0;
        foreach ($questions as $question){
            $limitQuestions [] = $question;
            $answers    = $em->getRepository('ModelBundle:Answer')
                            ->findByQuestion($question->getId());
            shuffle ($answers);
            $questionAnswers [$question->getId()] = $answers;

            if (++$i == $maxQuestions ) {break;} //Nº de preguntas por test
        }

        //var_dump($questions);
        return $this->render('WebBundle:Pages:test.html.twig',
                                                                array( 'subject'            =>    $subject,
                                                                       'subjects'           =>    $this->subjects,
                                                                       'categorySlug'       =>    $categorySlug,
                                                                       'subCategorySlug'    =>    $subCategorySlug,
                                                                       'questions'          =>    $limitQuestions,
                                                                       'questionAnswers'    =>    $questionAnswers,
                                                                       'level'              =>    $level));
    }


    /**
    * » Envío de los resultados de un cuestionario, y almacenamiento de las evaluaciones de las preguntas.
    *
    * @package PFC\WebBundle\Controller;
    * @version 1.0
    *
    * @param   string     $subjectSlug      slug de la asignatura
    * @param   integer    $level            Nivel del cuestionario. 0 indica aleatorio de cualquier nivel
    * @param   Request    $request          Objeto que contiene el formulario enviado
    *
    * @return  Render     Devuelve la vista WebBundle:Pages:test-post.html.twig con el resultado del cuestionario.
    */
    public function TestPostAction($subjectSlug, $level, Request $request)
    {

        $em             = $this->getDoctrine()->getManager();

        $subject        = $em->getRepository('ModelBundle:Subject')
                            ->findOneBySlug($subjectSlug);

        $testRequest = $request->request;

        $questionIds = array();
        $answers = array();

        $feedback = array();

        foreach ($testRequest as $asnwerId => $value) { //Hemos guadado la lista de ids de las preguntas en un array
            if ($asnwerId == 'submitB'){
                $questionIds = explode(',', $value);
            }
            else{
                $answers[$asnwerId] =  $value;
            }
        }

        $questions = array();
        foreach ($questionIds as $key => $value){ //reorganizamos los índices para que sea el id de la pregunta
            $questions[$value] = '';
            $questions[$value]['score'] = 0;
            $questions[$value]['answered'] = false;
        }


        $answerObj = new Answer();
        $questionObj = new Question();

        $countNumericBlankQuestions = 0;

        foreach ($answers as $answer => $value){ //Recorremos las respuestas
            list($QA, $id) = preg_split('[,]', $answer); //$QA almacena q o a, si el id es de una pregunta o una respuesta

            if ($QA == 'a'){ //preguntas Multichoice de respuesta múltiple (checkboxs)
                //Cargamos el objeto respuesta
                $answerObj = $em->getRepository('ModelBundle:Answer')
                                ->findOneById($id);

                //Cargamos el objeto pregunta de esa respuesta
                $questionObj = $answerObj->getQuestion();

                //Almacenamos el resultado de la pregunta, y le restamos la penalización en su caso
                $questions[$questionObj->getId()]['score'] =
                        $questions[$questionObj->getId()]['score'] +
                            (($answerObj->getValue() > 0) ? ($answerObj->getValue()) : (-1 * ($questionObj->getPenalty())));

                $questions[$questionObj->getId()]['answered'] = true;

            }else{ // Multichice de respuesta simple, o numéricas

                $questionObj = $em->getRepository('ModelBundle:Question')
                                ->findOneById($id);

                $questions[$questionObj->getId()]['answered'] = true;

                switch ($questionObj->getType()) {
                    case 0: //Multichoice

                        if ($questionObj->getSingle()){ //Confirmamos que sean de respuesta simple
                            $answerObj = $em->getRepository('ModelBundle:Answer')
                                ->findOneById($value);
                            $questions[$questionObj->getId()]['score'] =
                                (($answerObj->getValue() > 0) ? ($answerObj->getValue()) : (-1 * ($questionObj->getPenalty())));
                        }
                        break;

                    case 1: //trueFalse
                            $answerObj = $em->getRepository('ModelBundle:Answer')
                                ->findOneById($value);

                            $questions[$questionObj->getId()]['score'] =
                                    (($answerObj->getValue() > 0) ? ($answerObj->getValue()) : (-1 * ($questionObj->getPenalty())));
                        break;

                    case 6: //numerical

                        if ($value == '') {
                            $questions[$questionObj->getId()]['answered'] = false;
                            break;
                        }

                        $answerObjs = $em->getRepository('ModelBundle:Answer')
                                ->findByQuestion($id);

                        $value = trim(str_replace(",", ".", $value)); //Solo punto decimal



                        foreach ($answerObjs as $answerObj){

                            $questionValue = trim(str_replace(",", ".",$answerObj->getContent())); //Punto decimal

                            $tolerance = $answerObj->getTolerance();

                            $minValue = $questionValue - abs($tolerance);
                            $maxValue = $questionValue + abs($tolerance);

                            //ld($minValue . ' - ' . $maxValue . '  value = ' . $value);

                            if ( $value >= $minValue and $value <= $maxValue ) { //comparamos la respuesta dada. Incluimos la tolerancia a la respuesta
                                $questions[$questionObj->getId()]['score'] = $answerObj->getValue();
                            }

                        }
                        if ($questions[$questionObj->getId()]['score'] == 0){
                            $questions[$questionObj->getId()]['score'] = -1 * ($questionObj->getPenalty());
                        }
                        break;

                    default:
                        # code...
                        break;
                }
            }
        }

        $result = 0;
        $questionObj = new Question();

        $feedback['questions'] = count($questions);
        $feedback['answered'] = 0;
        $feedback['right'] = 0;
        $feedback['partiallyRight'] = 0;
        $feedback['incorrect'] = 0;


        foreach ($questions as $id => $question) {
            $result = $result +  $question['score'];

            $questionObj = $em->getRepository('ModelBundle:Question')
                                ->findOneById($id);

            if ($question['answered'] == true)
                $feedback['answered'] = $feedback['answered'] + 1;


            $questionObj->setNumAnswers($questionObj->getNumAnswers() + 1);
            
            if ($question['score'] == 100){
                //ld('correct', $question['score']);
                $questionObj->setNumCorrectAnswers($questionObj->getNumCorrectAnswers() + 1);
                $feedback['right'] = $feedback['right'] + 1;
            }elseif ($question['score'] > 0) { 
                //ld('Partially', $question['score']);
                $feedback['partiallyRight'] = $feedback['partiallyRight'] + 1;
            }elseif ($question['answered'] == true){ //score 0 o menos
                //ld('incorrect', $question['score']);
                $feedback['incorrect'] = $feedback['incorrect'] + 1;
            }

            $questionObj->setLevel((1-($questionObj->getNumCorrectAnswers() / $questionObj->getNumAnswers())) * 10);

            $em->persist($questionObj);
        }
        $em     ->flush();

        $result = ($result / count($questions) );

        //ld($feedback);

        if ($level == 0) {
            (($this->lang == 'en') ? ($level = ': random') : ($level = ': aleatorio'));
        }
        return $this->render('WebBundle:Pages:test-post.html.twig',
                                                                array( 'subject'            =>    $subject,
                                                                       'subjects'           =>    $this->subjects,
                                                                       'level'              =>    $level,
                                                                       'result'             =>    $result,
                                                                       'feedback'           =>    $feedback));
    }
}
