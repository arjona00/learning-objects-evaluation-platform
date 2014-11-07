<?php

namespace PFC\AdminBundle\Utils;

use PFC\AdminBundle\Entity as XMLEntities;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class importXml {

	private $fileName;
	public $pathDir;
	private $xmlQuiz;

    /**
     * Importa el archivo a procesar y obtiene los datos necesarios para construir las preguntas y sus respuestas
     *
     * @return integer
     */
	public function importFile()
    {
		$finder = new Finder();
        $finder->files()->in($this->pathDir)->name('*.xml');

        $path = '';
        foreach ($finder as $file) {
            // Print the relative path to the file
           $path = $file->getRelativePathname();
        }
        if ($path == '') {
            return 0; //Código de error
        }

        $path =  $this->pathDir . $path;

        try {
            $xmlFile = file_get_contents($path);

            $builder = SerializerBuilder::create();
            $serializer = $builder->build();
            $this->xmlQuiz = $serializer->deserialize($xmlFile, 'PFC\AdminBundle\Entity\XmlQuiz', 'xml');

            //var_dump($this->xmlQuiz);
            //ldd($this->xmlQuiz);

            //$xml = $serializer->serialize($this->xmlQuiz, 'xml');
            //ld($xml);
            //$fs->dumpFile($this->pathDir . 'file.txt', $xml); //crea un fichero con el contenido del texto.

        } catch (Exception $e) {
            echo 'Error en la serialización del archivo: ',  $e->getMessage(), "\n";
            return 1;
        }

        return -1;
    }

    /**
     * Construye las entidades Question y Answer y las persiste en la base de datos
     *
     * @param object $em           Objeto que representa un Entity Managers de Doctrine
     * @param object $subcategory  Objeto de la entidad Subcategory
     */
    public function saveFileToBd(ObjectManager $em, $subcategory)
    {
        $question_types = ['multichoice' => 0,
                           'truefalse' => 1,
                           'shortanswer' => 2,
                           'matching' => 3,
                           'cloze'=> 4,
                           'essay' => 5,
                           'numerical' => 6,
                           'description'=> -1,
                           'category' => -1];



    	foreach ($this->xmlQuiz->questions AS $questionXml) {
            if ($question_types[$questionXml->type] != -1){

                //var_dump($questionXml);
                $question = new Question();
    		    $question->setTitle(strip_tags($questionXml->name->text)); //Elimina etiqutas html. Para ignorar algunas, escribir strip_tags($text, 'etiqueta1,etiquta2')
    		    $question->setDescription($questionXml->questiontext->text);
    		    $question->setType($question_types[$questionXml->type]);

    		    $question->setLevel(5);
    		    $question->setNumAnswers(0);
    		    $question->setNumCorrectAnswers(0);
                $question->setSingle( is_null($questionXml->single) ? true : $questionXml->single );
                $question->setPenalty( is_null($questionXml->penalty) ? 0 : $questionXml->penalty * 100 );
                $question->setCategory($subcategory->getCategory());
                $question->setSubcategory($subcategory);
                $question->setSubject($subcategory->getSubject());

                $em ->persist($question);
                $em        ->flush();

                $question    = ObjectIdentity::fromDomainObject($question);

                $question   = $em
                                ->getRepository('ModelBundle:Question')
                                ->find($question->getIdentifier());

                $arrayAnswers = $questionXml->answers;
                if(!empty($arrayAnswers)){
                    foreach ($arrayAnswers as $answerXml) {

                        $answer = new Answer();
                        $answer->setContent(strip_tags($answerXml->text));
                        $answer->setTolerance(is_null($answerXml->tolerance) ? 0 : $answerXml->tolerance );
                        $answer->setValue($answerXml->fraction); //Hay preguntas multivaluadas por lo que este valor fluctua entre 0.0 y 100
                        $answer->setQuestion($question);

                        $em ->persist($answer);
                    }
                    $em->flush();
                }
            }
		}
    }

    public function __construct()
    {
        $this->xmlQuiz = new XMLEntities\XmlQuiz();
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     * @return importXml
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
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
};