<?php

namespace PFC\AdminBundle\Utils;

use PFC\ModelBundle\Entity\Answer;
use PFC\AdminBundle\Entity\AssessmentItem;
use PFC\ModelBundle\Entity\Question;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\Serializer\SerializerBuilder;

/**
 * Clase para el manejo de paquetes IMS QTI durante el procesos de importación
 *
 * @package PFC\AdminBundle\Controller;
 * @version 1.0
 *
 */
class importQTI
{
    //private $fileName;

    /**
     * Atributo que contiene la ruta al paquete de IMS QTI subido al servidor
     */
    public $pathDir;

    /**
     * Contiene las referencias a los archivos XML que contienen los Items de IMS QTI validos para esta aplicación, esto es, las URL parciales hacia esos
     *    archivos XML partiendo desde la raiz del paquete QTI
     */
    private $assessmentsRef;

    /**
     * Contiene Items validos para esta aplicación de IMS QTI serializados en un objeto de tipo PFC\AdminBundle\Entity\AssessmentItem
     */
    private $assessmentItemsXML;

    /**
     * Constructor de la clase
     *
     * @return PFC\AdminBundle\Entity\XmlManifest
     */
    public function __construct()
    {
        $this->assessmentItemsXML = new ArrayCollection();
    }

    /**
     * Importa el archivo a procesar y obtiene los datos necesarios para construir las preguntas y sus respuestas
     *
     * @return integer
     */
    public function importFile()
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in($this->pathDir)->name('*.zip');

        $path = '';

        $tempPath = sys_get_temp_dir()."/qti-pack/";
        $tempPath = str_replace('\\', '/', $tempPath);

        foreach ($finder as $file) {
           // Print the relative path to the file
           $path = $file->getRelativePathname();
        }

        if ($path == '') {
            return 2; // Código de error
        }

        $path = $this->pathDir.$path;

        if ($this->unzipQTI($path) === false) {
            return 2; // Código de error
        }


        try {
            // Se comprueba que exista un archivo de manifiesto
            if (!$fs->exists($tempPath."imsmanifest.xml")) {
                return 3; // Código de error
            }


            $manifestFile = file_get_contents($tempPath."imsmanifest.xml");

            if ($manifestFile === false) {
                return 4; // Código de error
            }

            $assessmentsRef = $this->getAssessmentsRef($manifestFile);

            if (empty($assessmentsRef)) {
                return 4; // Código de error
            }

            foreach ($assessmentsRef as $assessmentRef) {

                if (!$fs->exists($tempPath.$assessmentRef)) {
                    break; // Se detiene la ejecución del bucle devido a que la referencia a archivo del item es incorrecta, o el archivo no existe
                }

                $xmlFile = file_get_contents($tempPath.$assessmentRef);

                //ld("Ruta: ", $tempPath.$assessmentRef, " Contenido" ,$xmlFile);

                $xmlFile = str_replace('m:math', 'm_math', $xmlFile); //Sustituimos las etiquetas de dominio math
                $xmlFile = str_replace('m:annotation', 'm_annotation', $xmlFile); //Sustituimos las etiquetas de dominio annotation
                $xmlFile = strip_tags($xmlFile, AssessmentItem::TAGS); // Se elimina las etiquetas html del archivo para que no puedan perjudicar al proceso de serializado



                //ld("Contenido" ,$xmlFile);

                $builder = SerializerBuilder::create();
                $serializer = $builder->build();

                $assessmentItem = $serializer->deserialize($xmlFile, 'PFC\AdminBundle\Entity\AssessmentItem', 'xml');

                //ld("assessmentItem->itemBody" , $assessmentItem->itemBody);

                $assessmentItem->ref = $tempPath.$assessmentRef;

                $this->assessmentItemsXML [] = $assessmentItem;
            }

            $afterValidator = $this->assessmentItemsXML->count(); // Se guarda el número de cuestiones contenida en el array para comprobar si alguna se elimina luego de realizar la validación
            $this->assessmentItemsXML = $this->assessmentItemValidator($this->assessmentItemsXML);



            if ($this->assessmentItemsXML->count() === 0) { // Si no existen preguntas contenidas en el array
                //ld("No validado");
                return 4; // Código de error
            }


        }
        catch (Exception $e) {
            echo 'Error en la serialización del archivo imsmanifest.xml: ',  $e->getMessage(), PHP_EOL;
            return 1; //Código de error
        }

        if ($this->assessmentItemsXML->count() < $afterValidator) { // Si existen preguntas, el array es valido, pero si el validador a desechado algunas de ellas...
            return -2; // ...se devuelve codigo de importación parcial
        }
        return -1; // Código de importación correcta
    }

    /**
     * Construye las entidades Question y Answer y las persiste en la base de datos
     *
     * @param object $em           Objeto que representa un Entity Managers de Doctrine
     * @param object $subcategory  Objeto de la entidad Subcategory
     */
    public function saveFileToBd($em, $subcategory)
    {
        foreach ($this->assessmentItemsXML as $assessmentItemXML) {
            // Se obtiene un array con los valores mezclados de los array (ArrayCollection) choiceInteracion y extendeTextIteraction de un assessmentItem
            $items = array_merge($assessmentItemXML->itemBody->choiceInteraction->getValues(), $assessmentItemXML->itemBody->extendedTextInteraction->getValues());

            foreach ($items as $item) {
                // La siguenta variable guarda un array cuyos valores son el tipo y el single de la question
                $questionShape = $this->getQuestionShape($assessmentItemXML->responseDeclaration, $item);
                if ($questionShape['type'] !== -1) {
                    $question = new Question();

                    $question->setTitle($this->getQuestionTitle($item));
                    $question->setDescription($this->getQuestionDescription($item)); // No existe las descripciones como tal en IMS QTI
                    $question->setType($questionShape['type']);
                    $question->setLevel(5);
                    $question->setNumAnswers($this->getNumAnswers($item));
                    $question->setNumCorrectAnswers($this->getNumCorrectAnswers($assessmentItemXML->responseDeclaration, $item->responseIdentifier));
                    $question->setSingle($questionShape['single']);
                    $question->setPenalty($this->getQuestionPenality($assessmentItemXML->responseDeclaration, $item));
                    $question->setCategory($subcategory->getCategory());
                    $question->setSubcategory($subcategory);
                    $question->setSubject($subcategory->getSubject());
                    //ld($question);

                    $em->persist($question);

                    $arrayAnswers = $this->getQuestionAnswers($assessmentItemXML->responseDeclaration, $item);

                    if(!empty($arrayAnswers)) {
                        foreach ($arrayAnswers as $answerXml) {
                            $answer = new Answer();
                            $answer->setContent($this->getAnswerContent($answerXml, $item));
                            $answer->setValue($this->getAnswerValue($answerXml, $assessmentItemXML->responseDeclaration, $item));
                            $answer->setQuestion($question);
                            $answer->setTolerance(0);
                            //ld($answer);

                            $em ->persist($answer);
                        }
                    }
                }
            }
        }

        $em->flush();
    }

    /**
     * Valida que un objeto PFC\AdminBundle\Entity\AssessmentItem sea compatible con al aplicación. Su extructura es la siguiente:
     *
     *    AssessmentItem (title*, adactative)
     *     \_ ResponseDeclaration (identifier, cardinality)
     *     |   \_ CorrectResponse
     *     |   |   \_Value
     *     |   \_ Mapping (defaultValue)*
     *     |       \_ MapEntry (mapKey, mappedValue)
     *     \_ ItemBody (text*)
     *         \_ ChoiceInteraction (responseIdentifier)
     *         |   \_ Prompt
     *         |   \_ SimpleChoice (identifier, value)
     *         \_ ExtendedTextInteraction (responseIdentifier)
     *             \_ Prompt
     *     * No son obligatorios
     *
     * @param array $assessmentItems  Array de objetos de la clase 'PFC\AdminBundle\Entity\AssessmentItem', seralizados de los XML de en un paquete QTI
     * @return array  Devuelve el array de entrada con los elementos no compatibles eliminados
     */
    public function assessmentItemValidator($assessmentItems)
    {
        foreach ($assessmentItems as $key => $assessmentItem) {
            $thatsRight = true;

            $debug = false;

            // Se devolverá 'false' en los siguientes casos:
            if (!isset($assessmentItem)) { // Si no esta definido el 'AssessmentItem'
                $thatsRight = false;
                if ($debug) ld("Paso 1");
            }
            elseif (!isset($assessmentItem->adaptive, $assessmentItem->itemBody)) { // 'AssessmentItem' no contiene las propiedades 'adaptive' o 'itemBody'
                $thatsRight = false;
                if ($debug) ld("Paso 2");
            }

            if ($assessmentItem->responseDeclaration->count() == 0) { // La propiedad 'responseDeclaration' de 'AssessmentItem' está vacía
                $thatsRight = false;
                if ($debug) ld("Paso 3");
            }
            else {
                foreach ($assessmentItem->responseDeclaration as $responseDeclaration) {
                    if (!isset($responseDeclaration->correctResponse)) { // La propiedad 'correctResponse' de 'responseDeclaration' está vacía
                        $thatsRight = false;
                        if ($debug) ld("Paso 4");
                    }
                    elseif (empty($responseDeclaration->correctResponse->value)) { // La propiedad 'value' de 'correctResponse' está completamente vacía
                        $thatsRight = false;
                        if ($debug) ld("Paso 5");
                    }
                    else {
                        foreach ($responseDeclaration->correctResponse->value as $value) {
                            if (trim($value) === "") { // Algúno de los value de 'correctResponse' está vacío
                                $thatsRight = false;
                                if ($debug) ld("Paso 6");
                            }
                        }
                    }

                    if (isset($responseDeclaration->mapping)) {
                        if ($responseDeclaration->mapping->mapEntry->count() == 0) { // Si 'mapping' de 'responseDeclaración' existe y este no tiene algún 'mapentry'
                            $thatsRight = false;
                            if ($debug) ld("Paso 7");
                        }
                        else {
                            foreach ($responseDeclaration->mapping->mapEntry as $mapEntry) {
                                if (!isset($mapEntry->mapKey, $mapEntry->mappedValue)) { // Si alguno de los 'mapEntry' existentes no tiene las propiedades 'mapKey' o 'mappedValue'
                                    $thatsRight = false;
                                    if ($debug) ld("Paso 8");
                                }
                            }
                        }
                    }
                }
            }

            if ($assessmentItem->adaptive !== "false") { // La propiedad 'adaptive' de 'AssessmentItem' es dintinta de 'false'
                $thatsRight = false;
                if ($debug) ld("Paso 8");
            }

            if ($assessmentItem->itemBody->choiceInteraction->count() == 0 && $assessmentItem->itemBody->extendedTextInteraction->count() == 0) { // Si itemBody no contiene al menos un choiceInteraction o un extendedTextInteraction...
                $thatsRight = false;
                if ($debug) ld("Paso 9");
            }
            else {
                if ($assessmentItem->itemBody->choiceInteraction->count() > 0) {
                    foreach ($assessmentItem->itemBody->choiceInteraction as $choiceInteraction) {
                        if (!isset($choiceInteraction->responseIdentifier, $choiceInteraction->prompt)) { // Algún 'chioceInteraction' no tiene las propiedades 'responseIdentifier' o 'prompt'
                            $thatsRight = false;
                            if ($debug) ld("Paso 10");
                        }
                        else {
                            if (trim($choiceInteraction->prompt) === "") { // La propiedad 'prompt' de 'chioceInteraction' esta vacía
                                $thatsRight = false;
                                if ($debug) ld("Paso 11");
                            }

                            if (trim($choiceInteraction->responseIdentifier) === "") { // La propiedad 'responseIndentifier' de 'choiceInteraction' esta vacía
                                $thatsRight = false;
                                if ($debug) ld("Paso 12");
                            }
                            else {
                                $hasPair = false;

                                foreach ($assessmentItem->responseDeclaration as $responseDeclaration) {
                                    if ($choiceInteraction->responseIdentifier === $responseDeclaration->identifier) {
                                        $hasPair = true;
                                    }
                                }

                                if (!$hasPair) { // Algún 'choiceInteraction' no está asociado a algúno de los 'responseDeclaration' mediante sus propiedades 'responseIdentifier' e 'identifier' respectivamente
                                    $thatsRight = false;
                                    if ($debug) ld("Paso 14");
                                }
                            }
                        }

                        if ($choiceInteraction->simpleChoice->count() == 0) { // Alguno de los 'chioceInteraction' no tenga ninguna propiedad 'simpleChoice'
                            $thatsRight = false;
                            if ($debug) ld("Paso 15");
                        }
                        else {
                            foreach ($choiceInteraction->simpleChoice as $simpleChoice) {
                                if (!isset($simpleChoice->value, $simpleChoice->identifier)) { // Alguna propiedad 'simpleChoice' no contiene a su vez las propiedades 'value' o 'identifier'
                                    $thatsRight = false;
                                    if ($debug) ld("Paso 16");
                                }
                                else {
                                    if (trim($simpleChoice->value) === "") { // La propiedad 'value' de algún 'simpleChoice' este vacía
                                        $thatsRight = false;
                                        if ($debug) ld("Paso 17");
                                    }

                                    if (trim($simpleChoice->identifier) === "") { // La propiedad 'identifier' de algún 'simpleChoice' este vacía
                                        $thatsRight = false;
                                        if ($debug) ld("Paso 18");
                                    }
                                }
                            }
                        }

                        // Además, cada respuesta correcta, contenida en los 'responseDeclaretion' (responseDeclaration->correctResponse->value), tiene que esta asociada con algún valor (respuesta)
                        //    del 'choiceDeclaration' (choiceDeclaration->simpleChoice->value):
                        foreach ($assessmentItem->responseDeclaration as $responseDeclaration) {
                            if ($choiceInteraction->responseIdentifier === $responseDeclaration->identifier) { // Se busca el 'responseDeclaration' que está emparejado con el 'choiceInteraction'
                                if(isset($responseDeclaration->correctResponse) && !empty($responseDeclaration->correctResponse->value)) {
                                    foreach ($responseDeclaration->correctResponse->value as $value) { // Para cada 'correctResponse' (respuesta correcta)...
                                        $hasPair = false;
                                        foreach ($choiceInteraction->simpleChoice as $simpleChoice) {
                                            if ($value === $simpleChoice->identifier) { // ...se busca el 'simpleChoice' (respuesta) con el que empareja...
                                                $hasPair = true;
                                            }
                                        }

                                        if (!$hasPair) { // ...si no se consigue emparejarlas se devuelve 'false'
                                            $thatsRight = false;
                                            if ($debug) ld("Paso 20");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

               if ($assessmentItem->itemBody->extendedTextInteraction->count() > 0) {
                    foreach ($assessmentItem->itemBody->extendedTextInteraction as $interaction) {
                        if (!isset($interaction->responseIdentifier, $interaction->prompt)) { // Algún 'extendedTextInteraction' con contiene las propiedades 'responseIdentifier' y 'prompt'
                            $thatsRight = false;
                            if ($debug) ld("Paso 21");
                        }
                        else {
                            if (trim($interaction->prompt) === "") { // La propiedad 'prompt' de 'extendedTextInteraction' esta vacía
                                $thatsRight = false;
                                if ($debug) ld("Paso 22");
                            }

                            if (trim($interaction->responseIdentifier) === "") { // La propiedad 'responseIdentifier' de 'extendedTextInteraction' esta vacía
                            }
                            else {
                                $hasPair = false;

                                foreach ($assessmentItem->responseDeclaration as $responseDeclaration) {
                                    if ($interaction->responseIdentifier === $responseDeclaration->identifier) {
                                        $hasPair = true;
                                    }
                                }

                                if (!$hasPair) { // La propiedad 'identifier' de 'extendedTextInteraction' no esta asociada a ningún 'responseDeclaration'
                                    $thatsRight = false;
                                    if ($debug) ld("Paso 23");
                                }
                            }
                        }
                    }
                }
            }

            if ($thatsRight === false) {
                unset($assessmentItems [$key]);
            }
        }

        return $assessmentItems;
    }

    /**
     * Set pathDir
     *
     * @param string $pathDir
     *
     * @return importQTI
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
     * Obtiene cada uno de los campo 'resource' de un archivo de manifiesto (imsmanifest.xml) de IMS QTI
     *
     * @param object $manifest  XmlManifest deserializado del archivo 'imsmanifest.xml' con los datos de los resources
     * @return array
     */
    private function getAssessmentsRef ($manifest)
    {
        $crawler = new Crawler ($manifest);

        // Se busca los resouce del archivo de manifiesto
        $resources = $crawler->filterXPath('//resources//resource');

        $assessmentsRef = array (); // Esta variable contendrá todos los assessment de un archivo de manifiesto

        // Se obtiene las referencias a los archivos QTI de los campo 'resource' del manifiesto en los siguientes casos...
        for ($i=0; $i < $resources->count(); $i++) {
            $href = $resources->eq($i)->attr('href');
            $type = $resources->eq($i)->attr('type');

            // ...si es un item de la versión 2.1 de IMS QTI y es, además, un archivo XML...
            if (preg_match('/imsqti.*item.*v2p1/', $type) && preg_match('/.*\.xml$/', $href)) {
                $assessmentsRef [] = $href;
            }
        }

        return $assessmentsRef;
    }

    /**
     * Obtiene el valor para esta aplicación del tipo de pregunta (objeto Interaction en QTI) tomando la siguiente tabla como referencia:
     *
     *       ┌───────────────┬───────┬───────────────────────────────────────┐
     *       │ TIPO (MOODLE) │ VALOR │ POSIBLE EQUIVALENCIA EN IMS QTI       │
     *       ├───────────────┼───────┼───────────────────────────────────────┤
     *       │ multichoice   │   0   │ choiceInteraction                     │
     *       │ truefalse     │  -1   │ choiceInteraction                     │
     *       │ shortanswer   │   2   │ extendedTextInteraction               │
     *       │ matching      │   3   │ matchInteraction, gapMatchInteraction │
     *       │ cloze         │   4   │                                       │
     *       │ essay         │   5   │ extendedTextInteraction               │
     *       │ numerical     │   6   │ extendedTextInteraction               │
     *       │ description   │  -1   │                                       │
     *       │ category      │  -1   │                                       │
     *       └───────────────┴───────┴───────────────────────────────────────┘
     *
     * @param object $responseDeclarations  Contiene un objeto de la clase ResponseDeclaration que contiene todas las soluciones de un AssassmentItem
     * @param object $objectInteraction     Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return array  Devuelve un array con los valores tipo y single, utilizados para crear una entidad Question.php
     */
    private function getQuestionShape ($responseDeclarations, $objectInteraction)
    {
        $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ChoiceInteraction':
                foreach ($responseDeclarations as $responseDeclaration) {
                    if ($responseDeclaration->identifier === $objectInteraction->responseIdentifier) {

                        if (count($responseDeclaration->correctResponse->value) === 1) {
                            return array(
                                'type' => 0,
                                'single' => 1,
                            );
                        }

                        return array(
                            'type' => 0,
                            'single' => 0,
                        );
                    }
                }
                break;

            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                foreach ($responseDeclarations as $responseDeclaration) {
                    if ($responseDeclaration->identifier === $objectInteraction->responseIdentifier) {
                        $respValue = $responseDeclaration->correctResponse->value[0]; // Los objetos ExtendedTextInteraction sólo tiene una respuesta valida

                        if (count(explode(PHP_EOL, $respValue)) === 1 && is_numeric($respValue)) {
                            return array(
                                'type' => 6,
                                'single' => 1,
                            );
                        }
                    }
                }
                break;
        }

        return array(
            'type' => -1,
            'single' => -1,
        );
    }

    /**
     * Obtiene el título (enunciado) de una pregunta
     *
     * @param object $objectInteraction  Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return string
     */
    private function getQuestionTitle ($objectInteraction)
    {
       //var_dump($objectInteraction->prompt);

       $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                $promptText = trim($objectInteraction->prompt[0]->text); //Solo la parte de texto. Las fórmulas incluidas si las hay, van al campo description

                return  $promptText;
                break;

            default:
                return trim($objectInteraction->prompt); //Aquí también podrían incluir fórmulas en este tipo de preguntas
                break;
        }
    }

    /**
     * Obtiene el título (enunciado) de una pregunta
     *
     * @param object $objectInteraction  Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return string
     */
    private function getQuestionDescription ($objectInteraction)
    {
       //var_dump($objectInteraction->prompt);

       $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                $promptText = ""; //QTI, no tienen descripción, pero las fórmulas deben insertarse en las descripciones 

                if (isset($objectInteraction->prompt[0]->math)){
                    foreach ($objectInteraction->prompt[0]->math as $math) {
                        if(isset($math->annotation)){
                            foreach ($math->annotation as $annotation) {
                                if ($promptText == "")
                                    $promptText = '$$' . $annotation->text . '$$';
                                else 
                                    $promptText = $promptText . ' $$' . $annotation->text . '$$';
                            }
                        }
                    }
                }

                return  $promptText;
                break;

            default:
                return trim($objectInteraction->prompt); //Aquí también podrían incluir fórmulas en este tipo de preguntas
                break;
        }
    }

    /**
     * Obtiene el número de respuestas para una pregunta
     *
     * @param object $objectInteraction Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return integer
     */
    private function getNumAnswers ($objectInteraction)
    {
        $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ChoiceInteraction':
                return $objectInteraction->simpleChoice->count();
                break;

            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                return 1;
                break;
        }
    }

    /**
     * Obtiene el número de respuestas correctas para una pregunta
     *
     * @param object $responseDeclarations  Contiene un objeto de la clase ResponseDeclaration que contiene todas las soluciones de un AssassmentItem
     * @param string $responseIdentifier    Identificador de un Objeto de tipo interaction
     *
     * @return integer
     */
    private function getNumCorrectAnswers ($responseDeclarations, $responseIdentifier)
    {
        foreach ($responseDeclarations as $responseDeclaration) {
            if ($responseDeclaration->identifier === $responseIdentifier) {
                return count($responseDeclaration->correctResponse->value);
            }
        }
    }

    /**
     * Obtiene el valor de la penalización de una pretunta
     *
     * @param object $responseDeclarations  Contiene un objeto de la clase ResponseDeclaration que contiene todas las soluciones de un AssassmentItem
     * @param object $objectInteraction     Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return integer
     */
    private function getQuestionPenality ($responseDeclarations, $objectInteraction)
    {
        $totalPenality = 0;

        foreach ($responseDeclarations as $responseDeclaration) {
            if ($responseDeclaration->identifier === $objectInteraction->responseIdentifier) {
                if (isset($responseDeclaration->mapping)) {
                    $numAnswersWithPenality = 0;

                    foreach ($responseDeclaration->mapping->mapEntry as $map) {
                        // Si el valor de una pregunta (mappedValue) es negativo se considera palalización
                        if ($map->mappedValue < 0) {
                            $totalPenality = $map->mappedValue;
                            $numAnswersWithPenality++;
                        }
                    }
                    //ld($totalPenality);

                    // Si existe un valor default por defecto y es negativo se suma al penalty un numero de veces igual al total de
                    //    respuestas sin valor establecido
                    if (!empty($responseDeclaration->mapping->defaultValue) && $responseDeclaration->mapping->defaultValue < 0) {
                        $defaultValue = $responseDeclaration->mapping->defaultValue;

                        $numCorrectAnswers = $this->getNumCorrectAnswers($responseDeclarations, $objectInteraction->responseIdentifier);
                        $numAnswers = $this->getNumAnswers($objectInteraction);

                        $numAnswersWithoutValor = $numAnswers - $numCorrectAnswers - $numAnswersWithPenality;

                        $totalPenality = ($numAnswersWithoutValor * $defaultValue);

                        //ld($totalPenality);
                    }
                }
            }
        }

        return abs($totalPenality);
    }

    /**
     * Obtiene las respuestas asosiadas a una pregunta
     *
     * @param object $responseDeclarations  Contiene un objeto de la clase ResponseDeclaration que contiene todas las solucionesde un AssassmentItem
     * @param object $objectInteraction     Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return array
     */
    private function getQuestionAnswers ($responseDeclarations, $objectInteraction)
    {
        $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ChoiceInteraction':
                return $objectInteraction->simpleChoice->getValues();
                break;

            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                foreach ($responseDeclarations as $responseDeclaration) {
                    if ($responseDeclaration->identifier === $objectInteraction->responseIdentifier) {
                        return $responseDeclaration->correctResponse->value;
                    }
                }
                break;
        }
    }

    /**
     * Obtiene el enunciado (content) de una respuesta
     *
     * @param object $answer             Objeto de la clase SimpleChoise que contiene información sobre la estructura de una pregunta en QTI
     * @param object $objectInteraction  Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return string
     */
    private function getAnswerContent ($answer, $objectInteraction)
    {
        $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ChoiceInteraction':
                return trim($answer->value);
                break;

            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                return trim($answer[0]); // Para los preguntas de la clase ExtendedTextInteraction sólo hay una posible respuesta
                break;
        }
    }

    /**
     * Obtiene el valor de una respuesta, esto es un número comprendido entre 0 y 100, siendo normalmente la suma de todas las
     *     respuestas correctas igual a 100
     *
     * @param object $answer                Objeto de la clase SimpleChoise que contiene información sobre la estructura de una pregunta en QTI
     * @param object $responseDeclarations  Contiene un objeto de la clase ResponseDeclaration que contiene todas las soluciones de un AssassmentItem
     * @param object $objectInteraction     Objeto de tipo interaction que contiene información sobre la estructura de una pregunta en QTI
     * @return integer
     */
    private function getAnswerValue ($answer, $responseDeclarations, $objectInteraction)
    {
        $class = get_class($objectInteraction);

        switch ($class) {
            case 'PFC\AdminBundle\Entity\ChoiceInteraction':
                foreach ($responseDeclarations as $responseDeclaration) {
                    if ($responseDeclaration->identifier === $objectInteraction->responseIdentifier) {
                        if (isset($responseDeclaration->mapping)) {
                            foreach ($responseDeclaration->mapping->mapEntry as $map) {
                                if ($map->mapKey === $answer->identifier && $map->mappedValue >= 0) {
                                    return (int) $map->mappedValue;
                                }
                            }
                        }
                        else {
                            $numCorrectAnswers = $this->getNumCorrectAnswers($responseDeclarations, $objectInteraction->responseIdentifier);

                            $fraction = (int) floor(100 / $numCorrectAnswers);
                            $numAdjustment = 100 - ($fraction * $numCorrectAnswers);

                            foreach ($responseDeclaration->correctResponse->value as $value) {
                                if ($value === $answer->identifier) {
                                    if ($numCorrectAnswers <= $numAdjustment) {
                                        return $fraction + 1;
                                    }

                                    return $fraction;
                                }
                                $numCorrectAnswers--;
                            }
                        }
                    }
                }

                return 0; // Llegados a este punto la respuesta es incorrecta;
                break;

            case 'PFC\AdminBundle\Entity\ExtendedTextInteraction':
                return 100;
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    private function unzipQTI ($path)
    {
        $zip = new \ZipArchive();
        $openingResult = $zip->open($path);

        if ($openingResult === TRUE) {
            $tempDir = sys_get_temp_dir()."/qti-pack/";
            $tempDir = str_replace("\\", "/", $tempDir);

            $fs = new Filesystem();
            if ($fs->exists($tempDir)) {
                $fs->remove($tempDir);
            }

            $zip->extractTo($tempDir);

            $zip->close();
        }

        return $openingResult;
    }
}