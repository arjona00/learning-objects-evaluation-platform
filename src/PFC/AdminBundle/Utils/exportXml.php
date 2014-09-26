<?php
namespace PFC\AdminBundle\Utils;

use PFC\AdminBundle\Entity as XMLEntities;
use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Filesystem\Filesystem;
use JMS\Serializer\SerializerBuilder;

/**
* » Clase que realiza el proceso de exportación a moodleXML.
*
*/
class exportXml {

    private $xmlQuiz;
    private $fileName;
    public $pathDir;

    /**
    * » Recoge el contenido del objeto xmlQuiz y lo serializa. El resultado lo inserta el fichero que será exportado.
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @return  boolean     Devuelve verdadero si la operación se ha realizado correctamente.
    */
	public function saveFile() {

        $fs = new Filesystem();

        if ($this->xmlQuiz->questions->isEmpty()) {

            $this->fileName = 'emptyfile_' . time() . '.xml';
            $fs->dumpFile($this->pathDir . $this->fileName, "<quiz>No questions to export</quiz>");

        }else{

            $builder = SerializerBuilder::create();
            $serializer = $builder->build();

            $xml = $serializer->serialize($this->xmlQuiz, 'xml');

            $this->fileName = 'export_' . time() . '.xml';

            $fs->dumpFile($this->pathDir . $this->fileName, $xml);
        }
        return -1;
    }

    /**
    * » Inserta en el objeto xmlQuiz con las preguntas recibidas como parámetro.
    *
    * @package PFC\AdminBundle\Controller;
    * @version 1.0
    *
    * @param   manager     $em          Doctrine Manager
    * @param   array       $questions   array de preguntas para exportar.
    *
    */
    public function loadFromBd(ObjectManager $em, $questions){

        $question_types = ['multichoice',
                           'truefalse',
                           'shortanswer',
                           'matching',
                           'cloze',
                           'essay',
                           'numerical',
                           'description',
                           'category'];

        $questionsXml = new ArrayCollection();


    	foreach ($questions AS $question) {
            $questionXml = new XMLEntities\XmlQuestion();
            $questionXml->name->text = $question->getTitle();
            $questionXml->questiontext->text = $question->getDescription();
            $questionXml->type = $question_types[$question->getType()];
		    $questionXml->defaultgrade = 1;
		    $questionXml->penalty = $question->getPenalty() / 100;
            $questionXml->single = $question->getSingle();
            $questionXml->hidden = 0;

            $answers   = $em
                            ->getRepository('ModelBundle:Answer')
                            ->findByQuestion($question);

            $answersXml = new ArrayCollection();
            if(!empty($answers)){
                foreach ($answers as $answer) {
                    $answerXml = new XMLEntities\XmlAnswer();
                    $answerXml->text = $answer->getContent();
                    $answerXml->fraction = $answer->getValue();
                    if ($questionXml->type == 'numerical')
                        $answerXml->tolerance = $answer->getTolerance();
                    $answersXml->add($answerXml);
                }
            }
            $questionXml->answers = $answersXml;
            $questionsXml->add($questionXml);
		}
        $this->xmlQuiz->questions = $questionsXml;
        //var_dump($this->xmlQuiz);
    }

    public function __construct()
    {
        $this->xmlQuiz = new XMLEntities\XmlQuiz();
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set pathDir
     *
     * @param string $pathDir
     * @return importXml
     */
    public function setPathDir($pathDir)
    {
        $this->pathDir = $pathDir;

        return $this;
    }

    /**
     * Get pathDir
     *
     * @return string
     */
    public function getPathDir()
    {
        return $this->pathDir;
    }
}
