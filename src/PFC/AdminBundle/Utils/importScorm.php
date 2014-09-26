<?php
namespace PFC\AdminBundle\Utils;

use PFC\AdminBundle\Entity\XmlManifest;
use JMS\Serializer\SerializerBuilder;

use Symfony\Component\Filesystem\Filesystem;
//use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;

use Symfony\Component\DomCrawler\Crawler;

use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;

//use Doctrine\Common\Persistence\ObjectManager;
//use Symfony\Component\Security\Acl\Domain\ObjectIdentity;


/**
 * Clase mara el manejo de paquetes Scorm durante el procesos de importación
 *
 * @package PFC\AdminBundle\Controller;
 * @version 1.0
 *
 */
class importScorm {

    //private $fileName;
    public $pathDir;
    private $xmlScormManifest;

    /**
     * Constructor de la clase
     *
     * @return PFC\AdminBundle\Entity\XmlManifest
     */
    public function __construct()
    {
        $this->xmlScormManifest = new XmlManifest();
    }


    /**
     * Importa el archivo a procesar y obtiene los datos necesarios para construir las preguntas y sus respuestas
     *
     * @return integer
     */
    public function importFile() {
        $finder = new Finder();
        $finder->files()->in($this->pathDir)->name('*.zip');

        $path = '';

        foreach ($finder as $file) {
           // Print the relative path to the file
           $path = $file->getRelativePathname();
        }

        if ($path == '') {
            return 2; // Código de error
        }

        $path = $this->pathDir.$path;

        if ($this->unzipScorm($path) === false) {
            return 2; // Código de error
        }

        try {
            $manifestFile = file_get_contents(sys_get_temp_dir()."/scorm-pack/"."imsmanifest.xml");

            $builder = SerializerBuilder::create();
            $serializer = $builder->build();
            // Obtenemos sólo los campos Resoruces y Resoruce (hijos de Resoruces), asi como algunos de sus atributos, estos
            //     ultimos contienen un atribugo href que referencian varios archivos html los cuales contienen las preguntas
            //     y respuestas de las questions
            $this->xmlScormManifest = $serializer->deserialize($manifestFile, 'PFC\AdminBundle\Entity\XmlManifest', 'xml');

        }
        catch (Exception $e) {
            echo 'Error en la serialización del archivo imsmanifest.xml: ',  $e->getMessage(), "\n";
            return 1;
        }

        return -1;
    }

    /**
     * Guarda
     *
     * @param
     * @param
     * @return
     */
    public function saveFileToBd($em, $subcategory){
        $question_types = ['multichoice' => 0,
                           'truefalse' => 1,
                           'shortanswer' => 2,
                           'matching' => 3,
                           'cloze'=> 4,
                           'essay' => 5,
                           'numerical' => 6,
                           'description'=> -1,
                           'category' => -1];

        $manifestResources = $this->getResources($this->xmlScormManifest);

        $html = file_get_contents(sys_get_temp_dir()."/scorm-pack/".$manifestResources[1]->href);

        $crawler = new Crawler($html);
        $questionsHtml = $crawler->filter('div.question');

        for ($i=0; $i < $questionsHtml->count(); $i++) {
            $questionHtml = $questionsHtml->eq($i);

            $types = $this->getQuestionTypes($questionHtml, $question_types); // Buscarle un nombre mas intuitivo que 'types'

            if ($types ['type'] != -1) {
                $question = new Question();
                $question->setTitle(rtrim($questionHtml->filterXPath('//div//div')->text()));
                $question->setDescription('');
                $question->setType($types ['type']);
                $question->setLevel(5);
                $question->setNumAnswers(0);
                $question->setNumCorrectAnswers($this->getCorrectAnswers($crawler, $i));
                $question->setSingle($types ['single']);
                $question->setPenalty( is_null($questionXml->penalty) ? 0 : $questionXml->penalty );
                $question->setCategory($subcategory->getCategory());
                $question->setSubcategory($subcategory);
                $question->setSubject($subcategory->getSubject());

                //$em->persist($question);
                //$em->flush();

                $answersHtml = $questionHtml->filterXPath('//table//div');
                for ($j=0; $j < $answersHtml->count(); $j++) {
                    $answerHtml = $answersHtml->eq($j);

                    $answer = new Answer();
                    $answer->setContent($answerHtml->text());
                    //$answer->setValue(); //Hay preguntas multivaluadas por lo que este valor fluctua entre 0.0 y 100
                    $answer->setQuestion($question);

                    //$em ->persist($answer);
                    ld($answer);
                }
                //$em->flush();
            }
        }

    }

    /**
     * Set fileName
     *
     * @param string $fileName
     * @return imporScorm
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

    /**
     * Obtiene cada uno de los resources del campo 'resource' del 'imsmanifest.xml' de Scorm y los devuelve en un array
     *
     * @param $manifest objeto XmlManifest deserializado del archivo 'imsmanifest.xml' con los datos de los resources
     * @return array
     */
    private function getResources ($manifest) {
        foreach ($manifest as $arrayXmlResources) {
            foreach ($arrayXmlResources as $xmlResources) {
                foreach ($xmlResources as $arrayXmlResource) {
                    foreach ($arrayXmlResource as $xmlResource) {
                        $resources [] = $xmlResource;
                    }
                }
            }
        } // Habría que mejorarlo para que quedara más limpio

        return $resources;
    }

    /**
     * {@inheritDoc}
     */
    private function unzipScorm ($path) {
        $zip = new \ZipArchive();
        $openingResult = $zip->open($path);

        if ($openingResult === TRUE) {
            $tempDir = sys_get_temp_dir()."/scorm-pack/";

            $fs = new Filesystem();
            if ($fs->exists($tempDir)) {
                $fs->remove($tempDir);
            }

            $zip->extractTo($tempDir);

            $zip->close();
        }

        return $openingResult;
    }

    /**
     * {@inheritDoc}
     */
    private function getQuestionTypes ($questionHtml, $question_types) {
        $typeHtml = $questionHtml->filterXPath('//table//input')->attr('type');

        $types = array ();

        switch ($typeHtml) { // Habría que revisarlo
            case 'radio':
                $types ['type'] =  $question_types ['multichoice'];
                $types ['single'] = 1;
                break;
            case 'checkbox':
                $types ['type'] =  $question_types ['multichoice'];
                $types ['single'] = 0;
                break;
            case 'text':
            case 'number':
                $types ['type'] = $question_types ['numerical'];
                $types ['single'] = 1;
                break;
            default:
                $types ['type'] =  -1;
                break;
        }

        return $types;
    }

    /**
     * {@inheritDoc}
     */
    private function getCorrectAnswers($crawler, $numAnswer) {
        // Se extrae las etiquetas script del Html que tiene Scorm para las respuestas
        $scriptHtml = $crawler->filterXPath('//script');

        for ($i=0; $i < $scriptHtml->count(); $i++) {
            $scriptTxt = $scriptHtml->eq($i)->text();

            // Mientras se recorren los scripts se comprueba que alguno tenga el patrón como el siguiente "var key0 = 2",
            //    en el que corresponde el primer número con la pregunta y el segundo el número de la respuesta valida.
            if (preg_match_all ("/var key\d = \d/", $scriptTxt, $matches)) {
                return substr($matches[0][$numAnswer], -1);
            }
        }
    }
}