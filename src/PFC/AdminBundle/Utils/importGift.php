<?php

namespace PFC\AdminBundle\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Doctrine\Common\Persistence\ObjectManager;

use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;

class importGift
{
    private $fileName;
    public $pathDir;
    private $arrayQuestions;

    /**
     * Importa el archivo a procesar y obtiene los datos necesarios para construir las preguntas y sus respuestas
     *
     * @return integer
     */
    public function importFile() {

        $finder = new Finder();
        $finder->files()->in($this->pathDir)->name('*.txt');

        $path = '';
        foreach ($finder as $file) {
            // Print the relative path to the file
            $path = $file->getRelativePathname();
        }

        if ($path == '') {
            return 0; // Código de error
        }

        $path = $this->pathDir.$path;

        $text = file_get_contents($path);

        $this->arrayQuestions = array();
        $this->arrayQuestions = $this->getQuestionsArray($text);

        ld("After Validator", $this->arrayQuestions);

        $afterValidator = count($this->arrayQuestions); // Se guarda el número de cuestiones contenida en el array para comprobar si alguna se elimina luego de realizar la validación
        $this->arrayQuestions = $this->arrayQuestionsValidator($this->arrayQuestions); // Se verifica si todas las preguntas son correctas

        ld("Before Validator", $this->arrayQuestions);

        if (empty($this->arrayQuestions)) { // Si no existen preguntas contenidas en el array
            return 4; // Código de error11
        }

        if (count($this->arrayQuestions) < $afterValidator) { // Si existen preguntas, el array es valido, pero el validador a desechado algunas de ellas...
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
    public function saveFileToBd(ObjectManager $em, $subcategory)
    {
        foreach ($this->arrayQuestions as $singleQuestion) {
            $question = new Question();

            $question->setTitle(strip_tags($singleQuestion['title']));
            $question->setDescription(strip_tags($singleQuestion['description']));
            $question->setType($singleQuestion['type']);
            $question->setLevel(5);
            $question->setNumAnswers($singleQuestion['numAnswers']);
            $question->setNumCorrectAnswers($singleQuestion['numCorrectAnswers']);
            $question->setSingle($singleQuestion['single']);
            $question->setPenalty($singleQuestion['penalty']);
            $question->setCategory($subcategory->getCategory());
            $question->setSubcategory($subcategory);
            $question->setSubject($subcategory->getSubject());
            //ld($question);

            $em->persist($question);

            foreach ($singleQuestion['answers'] as $answers) {
                $answer = new Answer();
                $answer->setContent(strip_tags($answers['content']));
                $answer->setValue($answers['value']);
                $answer->setTolerance($answers['tolerance']);
                $answer->setQuestion($question);
                //ld($answer);

                $em->persist($answer);
            }
        }

        $em->flush();
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

    /**
     * Construye un array con preguntas y respuestas, extraidas del texto del archivo GIFT importado.
     *
     * El formato de Gift es el siguiente:
     *
     *    // text                    -> Comentario hasta el fin de la linea (opcional).
     *    ::title::                  -> Título de la pregunta (opcional). En esta aplicación se le denomína 'description'.
     *    text                       -> Texto de la pregunta (el enunciado).
     *    [...format...]             -> El formato de los siguientes bit de texto. pueden ser [html], [plain], [markdown] y [moodle], se encuentra siempre delante del texto.
     *    {                          -> Inicio de la(s) respuesta(s) de una pregunta.
     *    {T} o {F}                  -> Verdadoro of falso para las respuestas de este tipo (tambíen puede ser {TRUE} o {FALSE}).
     *    { ... =right ... }         -> Respuesta correcta para preguntas de tipo multiple-choice (de una sóla respuesta) y Short-Answer.
     *    { ... ~wrong ... }         -> Respuesta incorrecta para multiple-choice (de una sóla respuesta) o simplemente una respesta para las multi-evaluados.
     *    { ... =item -> match ... } -> Respuesta para preguntas de tipo Matching.
     *    #feedback text             -> Feedback de las preguntas. Se coloca inmediatamente después del texto de una respuesta. Ej.: =Ulysses S. Grant#Correct, Gratz!!!.
     *    ~%n%answer                 -> n porcentaje de acierto/penalty (dependiendo si n es positivo o negativo) para una respuesta Multi-Chioce.
     *    {#                         -> Inicio de la(s) respuesta(s) de una pregunta de tipo numeric.
     *    answer:tolerance           -> Respuesta correcta para una pregunta de tipo numeric dentro de una ragon de ± tolerancia.
     *    low..high                  -> Valores de menor y mayor, respectivamente, para un rango en una respuesta Numeric.
     *    =%n%answer:tolerance       -> n porcentaje de acierto/penalty (dependiendo si n es positivo o negativo) para una una respuesta Numeric multi-evaluada.
     *    }                          -> Fin de la(s) respuesta(s) para un pregunta.
     *    \character                 -> Carácter de escape para aquellos con significado especial en Gift: '~', '=', '#,'' '{', '}' y ':'.
     *    \n                         -> Caráctar que representa un salto de linea para el texto ('text') de una pregunta.
     *
     * @param  string $text  Texto extraido del archivo Gift subido al servidor
     * @return array
     */
    private function getQuestionsArray($text)
    {
        $questions = array();

        // Se separa el texto en un array linea por linea
        $lines = explode("\n" , $text); //PHP_EOL

        // Se agrupan las lineas que conforman el texto en bruto de cada pregunta por separado y se elimina resto de lineas (comentarios y vacías)
        $index = 0;
        $questionsGift = array();

        foreach ($lines as $line) {
            $line = trim($line); // Primero se le quita los espacios en blanco y otros caracteres no deseados (como tabuladores o saltos de linea)...
            $line = $this->giftScapeChars2PlaceHolders($line); // ...luego se sustituye los caracteres de escape de Gift por los marcadores de esta aplicación

            // Si es un comentario o una linea vacía se ignora
            if (substr($line, 0, 2) !== "//" && $line !== "") {
                if (isset($questionsGift[$index])) {
                    $questionsGift[$index] .= $line;
                }
                else {
                    $questionsGift[$index] = $line;
                }
            }
            elseif ($line === "") {
                $index++; // Para agrupar todas las lineas de una misma pregunta se sumara +1 al indice del array sólo cuando encuentre una linea en blanco
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Se extraer los distintos elementos de las preguntas (enunciado, tipo, respuestas, etc) Pasa guardarlos de forma ordenada en  //
        //    otro array (que será devuelto por el método), para ello se recorre el array extrayendo el texto de la pregunta para así   //
        //    calcular los distintos elementos.                                                                                         //
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        foreach ($questionsGift as $key => $questionGift) {
            // Para que tenga un formato válido debe tener un sólo corchete de apertura '{' y otro de cierre '}' para delimitar la(s) respuesta(s)
            if (substr_count($questionGift, '{') !== 1 || substr_count($questionGift, '}') !== 1) {
                $type = -2; // Error de formato
            }

            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Si extrae la respuesta, delimitada entre corchetes, del resto del texto para procesarlo, luego de obtener la respuesta   //
            //    lo primero que se hace es obtener el tipo de la misma:                                                                //
            //                                                                                                                          //
            //     ┌───────────────┬───────┐                                                                                            //
            //     │ TIPO (MOODLE) │ VALOR │                                                                                            //
            //     ├───────────────┼───────┤                                                                                            //
            //     │ multi-choice  │   0   │                                                                                            //
            //     │ true-false    │   1   │                                                                                            //
            //     │ shortanswer   │   2   │                                                                                            //
            //     │ matching      │  -1   │                                                                                            //
            //     │ cloze         │  -1   │                                                                                            //
            //     │ essay         │  -1   │                                                                                            //
            //     │ numerical     │   6   │                                                                                            //
            //     │ description   │  -1   │                                                                                            //
            //     │ category      │  -1   │                                                                                            //
            //     │ sigle-choice* │  9(0) │                                                                                            //
            //     └───────────────┴───────┘                                                                                            //
            //     * El tipo single-choice tiene el valor 9 para esta clase, para el resto de la aplicación tiene un type = 0 con       //
            //          single = 1                                                                                                      //
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Se extrae el texto de la(s) respuesta(s) en formato Gift
            $answerGift = trim(preg_replace('/.+({.*}).*/', '$1', $questionGift));

            // Si encuentra alguna cadena de texto detrás de la última llave (si no encuentra nada devolvería la cadena
            //    pasada como argumento intacta) y esa cadena no es un punto se trata de una respuesta de tipo cloze
            if (preg_replace('/.+\{.+\}(.+)/','$1', $questionGift) !== $questionGift && preg_replace('/.+\{.+\}(.+)/','$1', $questionGift) !== '.') { // Cloze, siempre incluye texto detrás de la respuesta (no se tiene encuenta esta regla si ese texto es solo un punto)
                $type = -1;
            }
            elseif (!preg_match('/\{(.+)\}/', $answerGift)) { // Essay, no contiene texto entre los corchetes
                $type = -1;
            }
            elseif (preg_match('/->/', $answerGift)) { // Matching, asocia los elementos medienta la cadena '->'
                $type = -1;
            }
            elseif (substr($answerGift, 1, 1) === '#') { // Numerical, empieza en '{#'
                $type = 6;
            }
            elseif (preg_match('/^{(T|TRUE|F|FALSE)(#\w+|})/', $answerGift)) { // True-False, sólo incluye el texto 'T', 'TRUE', 'F', 'FALSE' y un posible feedback
                $type = 1;
            }
            elseif (preg_match('/~/', $answerGift) && preg_match('/=/', $answerGift)) { // Single-Choice (al descartar matching, numerical y Cloze), incluye, al principio de cada respuesta un '=' para las correcta y '~' para las incorrectas
                $type = 9;
            }
            elseif (preg_match('/~/', $answerGift)) { // Multi-Choice (al descartar single-choice, matching, numerical y Cloze), marca todas las respuestas con '~' al principio
                $type = 0;
            }
            elseif (preg_match('/=/', $answerGift)) { // Short-Answer (al descartar matching, missing-word, numerical y multichoice), marca todas las respuestas con '=' al principio
                $type = -1;
            }
            else { // Desconocido, se llegaría a este punto si la respuesta tiene errores o no se trata de un formato válido de Gift
                $type = -2; // Error de formato
            }

            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Una vez obtenido el tipo y verificado de que se trata de un formato válido, se procede a extraer el texto no             //
            //    correspospondiente a la(s) respuesta(s), cerrado entre corchetes, para obtener de él enunciado y la descripción       //
            //    de la misma                                                                                                           //
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Se ignora aquellas respuestas cuyo type sea -1 por ser incorrecta o no válidas
            if ($type >= 0) {

                // Se añade el tipo al array
                $questions[$key]['type'] = $type;

                // Se extrae la parte de texto de la pregunta en formato Gift correspondiente al enunciado y el titulo (en caso de que exista)
                $titleGift = trim(preg_replace('/(.+){.+}.*/', '$1', $questionGift));

                // Se extrae el comentario del título y se asigna a description, en caso de que exista, y se incluye el resto del texto como enunciado
                if (substr($titleGift, 0, 2) === '::') {
                    $endTitle = strpos($titleGift, '::', 2);

                    $title = substr($titleGift, 2, $endTitle - 2);
                    $description = substr($titleGift, $endTitle + 2);

                    $questions[$key]['title'] = $this->placeHolders2Char($title); // Se sustituye los marcadores antes de asignar
                    $questions[$key]['description'] = $this->placeHolders2Char($description); // Se sustituye los marcadores antes de asignar
                }
                else { // En saco contrario, todo el texto se asiga al enunciado y se deja vacía la descripción
                    $questions[$key]['title'] = $this->placeHolders2Char($titleGift); // Se sustituye los marcadores antes de asignar
                    $questions[$key]['description'] = "";
                }

                // Se elimina la etiqueta format, en caso de que exista, del enunciado (nota: normalmente se encuentra en la parte extraida para al title, pero por motivos de compatibilidad se buscará también en la parte correspondiente al description)
                if (substr($questions[$key]['title'], 0, 1) === '[') {
                    $endFormat = strpos($questions[$key]['title'], ']');

                    $questions[$key]['title'] = substr($questions[$key]['title'], $endFormat + 1);
                }
                if (substr($questions[$key]['description'], 0, 1) === '[') {
                   $endFormat = strpos($questions[$key]['description'], ']');

                    $questions[$key]['description'] = substr($questions[$key]['description'], $endFormat + 1);
                }

                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // Se obtiene las respuestas del texto en formato Gift, a partir de ellas se establece el número de respuesta, el número de //
                //    respuestas correctas, el single, el enunciado y el valor y penalización de cada respuestas. El proceso dependerá del  //
                //    tipo de la pregunta                                                                                                   //
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                // Primero se quitan los corchetes de inicio y final
                $answerGift = substr($answerGift, 1, -1);

                switch ($questions[$key]['type']) {
                    case 0: // MultiChoice
                        $answerGift = str_replace(array('~', '#'), array(' ~', ' #'), $answerGift); // Se añade un espacio en blanco delante de los caracteres especiales  '=', ' ~' y ' #'

                        $answerGiftFragments = explode(' ', $answerGift); // Se separa la respuesta en un array con el espacio como delimitador

                        $index = 0; // Índice para el array de respuestas, en cada vuelta comenzará en cero
                        $lastPart = ""; // Contendrá el tipo (respuesta correcta, incorrecta o feedback) de la última comprobación
                        $correctAnswers = 0; // Aumentará en uno por porcentaje positivo
                        $penalty = 0; // Aumentará con los porcentajes negativos, si los hubiera

                        // Se recorre el array de las respuesta en Gift para extraer los fragmentos texto y formar las respuestas
                        foreach ($answerGiftFragments as $answerGiftFragment) {
                            $firstChar = substr(trim($answerGiftFragment), 0, 1);

                            if ($firstChar === '~') { // Si se trata de una respuesta
                                $index++; // Se crea un nuevo índice para la nueva respuesta, su valor en si no es importante
                                $lastPart = 'answer'; // Se establece la variable lastPart como 'answer'

                                // Se comprueba si tiene un porcentaje el texto
                                if (substr($answerGiftFragment, 1, 1) === '%') { // Tiene porcentaje
                                    $endPercent = strpos($answerGiftFragment, '%', 2);

                                    $percent = (int) substr($answerGiftFragment, 2, $endPercent-1); // Se extrae su valor

                                    if ($percent > 0) { // Si el porcentaje es positivo se asigna como el valor de la respuesta
                                        $questions[$key]['answers'][$index]['value'] = $percent;
                                        $correctAnswers++; // Con el porcentaje positivo se considera la respuesta como correcta
                                    }
                                    else { // Si es negativo ó 0 el porcentaje se considera penalización y su valor se asigna a 0
                                        $questions[$key]['answers'][$index]['value'] = 0;
                                        if ($percent > $penalty) {
                                            $penalty = abs($percent);
                                        }
                                    }

                                    // Luego se asigna el contenido de la respuesta quitando el porcentaje y el símbolo '~'
                                    $content = substr($answerGiftFragment, $endPercent+1, strlen($answerGiftFragment) - $endPercent);

                                    // Se asigna el valor del contenido y la tolerancia se deja en 0
                                    $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);
                                    $questions[$key]['answers'][$index]['tolerance'] = 0;
                                }
                                else { // En caso de que no tenga porcentaje:
                                    // Se asigna un valor a 0 y todo el contenido como texto (menos el simbolo '~') al contenido de la pregunta
                                    $questions[$key]['answers'][$index]['value'] = 0;
                                    $content = substr($answerGiftFragment, 1);

                                    // Se asigna el valor del contenido y la tolerancia, que se asigna a 0
                                    $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);
                                    $questions[$key]['answers'][$index]['tolerance'] = 0;
                                }
                            }
                            elseif (substr($answerGiftFragment, 0, 1) === '#') { // Feedback, que es ignorado...
                                $lastPart = 'feed'; // ...pero se establecerá la variable lastPart
                            }
                            // Al separa el texto en un array con el espacio en blanco como delimitador las respuestas y el feedback pueden quedar fragmentados, por tanto
                            //    si se trata de una cadena no vacía que no comienza por '~' o '#' formará parte del texto anterior, ya sea respesta o feedback, así que
                            //    se une a él (sólo en caso de que se trate de una respuesta)
                            elseif ($answerGiftFragment !== "" && $lastPart === "answer") {
                                $content = $content . ' ' . $answerGiftFragment;
                                $content = $this->placeHolders2Char($content);
                            }
                        }

                        // Finalmente se asigna los valores para numAnswers, numCorrectAnswers, single y penalty
                        $questions[$key]['numAnswers'] = count($questions[$key]['answers']);
                        $questions[$key]['numCorrectAnswers'] = $correctAnswers;
                        $questions[$key]['penalty'] = $penalty;
                        $questions[$key]['single'] = 0;
                        break;

                    case 1: // True-False
                        // Este tipo de pregunta se interpreta como una sigle-choice con dos respuestas: verdarero y false, así que primero se asigna el contenido de esas dos respuestas
                        $questions[$key]['answers'][0]['content'] = 'True';
                        $questions[$key]['answers'][1]['content'] = 'False';

                        $firstChar = substr($answerGift, 0, 1);

                        // Si la respuesta es T o TRUE el valor 100 se asigna a la respuesta 'Verdadero'
                        if ($firstChar === 'T') {
                            $questions[$key]['answers'][0]['value'] = 100;
                            $questions[$key]['answers'][1]['value'] = 0;
                        }
                        else { // En caso contrario (Respuesta F o FALSE), el valor 100 se asigna a la respuesta 'Falso'
                            $questions[$key]['answers'][0]['value'] = 0;
                            $questions[$key]['answers'][1]['value'] = 100;
                        }

                        // Se asigna la tolerancia, que sería igual a 0 ya que no se trata de una respuesta númerica
                        $questions[$key]['answers'][0]['tolerance'] = 0;
                        $questions[$key]['answers'][1]['tolerance'] = 0;

                        // Para esta aplicación las True-False se considerarán como Multi-Choice con single igual a 1
                        $questions[$key]['type'] = 0; // Tipo multichoice

                        // Finalmente se asigna los valores para tolerance, numAnswers, numCorrectAnswers, single y penalty
                        $questions[$key]['numAnswers'] = 2;
                        $questions[$key]['numCorrectAnswers'] = 1;
                        $questions[$key]['penalty'] = 0;
                        $questions[$key]['single'] = 1;
                        break;

                    case 6: // Numeric
                        // El proceso será distinto si las respuestas son precedidas de un simbolo '=', indicativo de pregunta numerica multi-evaluada
                        if (preg_match('/=/', $answerGift)) {
                            $answerGift = str_replace(array('=', '#'), array(' =', ' #'), $answerGift); // Se añade un espacio en blanco delante de los caracteres especiales  '=' y ' #'

                            $answerGiftFragments = explode(' ', $answerGift); // Se separa en un array con el espacio en blanco como delimitador

                            $index = 0; // Índice para el array de respuestas, en cada vuelta comenzará en cero
                            $correctAnswers = 0; // Aumentará en uno por porcentaje positivo

                            // Se recorre el array de las respuesta en Gift para extraer los fragmentos texto y formar las respuestas
                            foreach ($answerGiftFragments as $answerGiftFragment) {
                                $firstChar = substr(trim($answerGiftFragment), 0, 1);

                                if ($firstChar === '=') { // Si se trata de una respuesta
                                    $index++; // Se crea un nuevo indice para la nueva respuesta

                                    // Se comprueba si tiene un porcentaje
                                    if (substr($answerGiftFragment, 1, 1) === '%') { // Si existe un porcentaje
                                        $endPercent = strpos($answerGiftFragment, '%', 2);

                                        // Se extrae y se asigna como valor de la respuesta
                                        $value = (int) substr($answerGiftFragment, 2, $endPercent-1);

                                        // Se extrae el contenido de la respuesta quitando el porcentaje y el símbolo '='
                                        $content = substr($answerGiftFragment, $endPercent+1, strlen($answerGiftFragment) - $endPercent);
                                    }
                                    else { // No existe porcentaje
                                        $value = 100;

                                        // Se extrae del contenido de la respuesta, quitando el símbolo '='
                                        $content = substr($answerGiftFragment, 1, strlen($answerGiftFragment) - 1);
                                    }

                                    // Se comprueva si existe rango o tolerancia en el contenido
                                    if (preg_match('/\.\./', $content)) { // Si existe rango
                                        // Se estrae los valores primero y ultimo del rango
                                        $lastNum = strstr($content, '..');
                                        $lastNum = substr($lastNum, 2, strlen($lastNum) - 2); // Al lastNum hay que quitarle dos puntos los puntos '::'
                                        $firstNum = strstr($content, '..', true);

                                        // El contente será igual a la media de ambas y la tolerancia a su diferencia entre 2
                                        $content = ($lastNum + $firstNum) / 2;
                                        $tolerance = ($lastNum - $firstNum) / 2;
                                    }
                                    elseif (preg_match('/:/', $content)) { // Si existe tolerancia
                                        // Se separa el contenido de la respuesta de la tolerancia
                                        $tolerance = strstr($content, ':');
                                        $content = strstr($content, ':', true);
                                        // Se le quita los dos puntos del principio a la tolerancia
                                        $tolerance = substr($tolerance, 1, strlen($tolerance) - 1);

                                    }
                                    else {
                                        $tolerance = 0;
                                    }

                                    // Se asigna el valor del contenido, el value y la tolerancia
                                    $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);
                                    $questions[$key]['answers'][$index]['value'] = $value;
                                    $questions[$key]['answers'][$index]['tolerance'] = $tolerance;
                                }
                            }
                        }
                        else { // Las preguntas numeric sin el símbolo '=' indica una sóla respuesta
                            // Se le quita el simbolo '#' del principio de la respuesta
                            $content = substr($answerGift, 1, strlen($answerGift) - 1);

                            // Se comprueba de que tengo feedback, en caso afirmativo quita también
                            if (preg_match('/#/', $content)) {
                                $content = strstr($content, '#', true);
                            }

                            // Se comprueva si existe rango o tolerancia en el contenido
                            if (preg_match('/\.\./', $content)) { // Si existe rango
                                // Se estrae los valores primero y ultimo del rango
                                $lastNum = strstr($content, '..');
                                $lastNum = substr($lastNum, 2, strlen($lastNum) - 2); // Al último hay que quitarle los puntos
                                $firstNum = strstr($content, '..', true);

                                // El contente será igual a la media de ambas y la tolerancia a su diferencia entre 2
                                $tolerance = ($lastNum - $firstNum) / 2;
                                $content = ($lastNum + $firstNum) / 2;
                            }
                            elseif (preg_match('/:/', $content)) { // Si existe tolerancia
                                // Se separa el contenido de la respuesta de la tolerancia
                                $tolerance = strstr($content, ':');
                                $content = strstr($content, ':', true);
                                // Se le quita los dos puntos del principio a la tolerancia
                                $tolerance = substr($tolerance, 1, strlen($tolerance) - 1);

                            }
                            else {
                                $tolerance = 0;
                            }

                            // Se asigna el valor del contenido, el value y la tolerancia
                            $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);
                            $questions[$key]['answers'][$index]['value'] = 100;
                            $questions[$key]['answers'][$index]['tolerance'] = $tolerance;
                        }

                        $questions[$key]['numAnswers'] = count($questions[$key]['answers']);
                        $questions[$key]['numCorrectAnswers'] = count($questions[$key]['answers']);
                        $questions[$key]['penalty'] = 0;
                        $questions[$key]['single'] = 1;
                        break;

                    case 9: // Single-Choice (para el resto de la aplicación será multi-choice, con type = 0 y single = 1)
                        $answerGift = str_replace(array('=', '~', '#'), array(' =', ' ~', ' #'), $answerGift); // Se añade un espacio en blanco delante de los caracteres especiales  '=', ' ~' y ' #'

                        $answerGiftFragments = explode(' ', $answerGift); // Se separa en un array con el espacio como delimitador

                        $index = 0; // Índice para el array de respuestas, en cada vuelta comenzará en cero
                        $lastPart = ""; // Contendrá el tipo (respuesta correcta, incorrecta o feedback) de la última comprobación
                        $penalty = 0; // Aumentará con los porcentajes negativos si los hubiera

                        foreach ($answerGiftFragments as $answerGiftFragment) {
                            $firstChar = substr($answerGiftFragment, 0, 1);

                            if ($firstChar === '~') { // Respuesta incorrecta
                                $index++; // Se crea un nuevo índice para la nueva respuesta, su valor en si no es importante
                                $lastPart = 'answer'; // Se establece la variable lastPart como 'answer'

                                // Se comprueba si existe porcentaje
                                if (substr($answerGiftFragment, 1, 1) === '%') { // En el caso de que lo tenga:
                                    $endPercent = strpos($answerGiftFragment, '%', 2);

                                    // Se extrae el valor del porcentaje
                                    $percent = (int) substr($answerGiftFragment, 2, $endPercent-1);

                                    // Se guarda la penalización si es mayor que la actual
                                    if ($percent > $penalty) {
                                        $penalty = abs($percent);
                                    }

                                    // Al contenido de la respuesta se le quita la parte que corresponde con el porcentaje
                                    $content = substr($answerGiftFragment, $endPercent+1, strlen($answerGiftFragment) - $endPercent);
                                }
                                else { // En caso de que no tenga porcentaje:
                                    // Se asigna todo el contenido como texto (quitandole el simbolo '~')
                                    $content = substr($answerGiftFragment, 1);
                                }

                                // Se asigna el contenido de la respuesta, quitando el porcentaje y el símbolo '~'
                                $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);

                                // Para la respuesta incorrect de single-choice se asigna 0 al value
                                $questions[$key]['answers'][$index]['value'] = 0;

                                // La tolerancia se asigna con valor igual a 0
                                $questions[$key]['answers'][$index]['tolerance'] = 0;
                            }
                            elseif ($firstChar === '=') { // Respuesta correcta
                                $index++; // Se crea un nuevo índice para la nueva respuesta, su valor en si no es importante
                                $lastPart = 'answer'; // Se establece la variable lastPart como 'answer'

                                // Se asigna el contenido, quitando el símbolo '='
                                $content = substr($answerGiftFragment, 1, strlen($answerGiftFragment) -1);
                                $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);

                                // Para la respuesta correcta de single-choice se asigna 100 al value
                                $questions[$key]['answers'][$index]['value'] = 100;

                                // La tolerancia se asigna con valor igual a 0
                                $questions[$key]['answers'][$index]['tolerance'] = 0;
                            }
                            elseif (substr($answerGiftFragment, 0, 1) === '#') { // Feedback, que es ignorado...
                                $lastPart = 'feed'; // ...pero se establece la variable lastPart como 'feed'
                            }
                            elseif ($answerGiftFragment !== "" && $lastPart === "answer") { // Si se trata de un texto no vacío y que no comienza por '=', '~' o '#'; forma parte del texto de la anterior respesta o feedback, por tanto se une a él en el primer caso
                                $content = $questions[$key]['answers'][$index]['content'] . ' ' . $answerGiftFragment;
                                $questions[$key]['answers'][$index]['content'] = $this->placeHolders2Char($content);
                            }

                        }

                        // Finalmente se asigna los valores para numAnswers, numCorrectAnswers, single y penalty
                        $questions[$key]['numAnswers'] = count($questions[$key]['answers']);
                        $questions[$key]['numCorrectAnswers'] = 1;
                        $questions[$key]['penalty'] = $penalty;
                        $questions[$key]['single'] = 1;
                        $questions[$key]['type'] = 0; // Aunque para esta aplicación su valor es 9 para el resto de la aplicación es 0
                        break;
                }
            }
        }

        return $questions;
    }

    /**
     * Valida el array de preguntas obtenido del archivo Gift y elimina aquellas entradas que no sean válidas.
     *    El Array tiene la siguiente estructura:
     *
     *    arrayQuestions
     *     \_ type (string)
     *     \_ title (string)
     *     \_ description (string)
     *     \_ answers (array)
     *     |   \_ Content (string)
     *     |   \_ Value (integer)
     *     |   \_ Tolerance (integer)
     *     \_ numAnswers (integer)
     *     \_ numCorrectAnswers (integer)
     *     \_ penalty (integer)
     *     \_ single (integer)
     *
     *
     * @param  array $questions  Array de preguntas y respuestas extraido de un archivo en formato Gift subido al servidor
     * @return array
     */
    private function arrayQuestionsValidator ($questions)
    {
        // Un elemento del array questions estará mal si se cumple los siguientes casos:
        foreach ($questions as $key => $question) {
            $thatsRight = true;

            // Si no existe alguno de los elementos del array (a excepción de 'type' que llegados a este punto tiene que estar)
            if (!isset($question['title'], $question['description'],  $question['answers'], $question['numAnswers'], $question['numCorrectAnswers'], $question['penalty'], $question['single'])) {
                $thatsRight = false;
            }
            else {
                // Si 'title' está vacío
                if (empty($question['title'])) {
                    $thatsRight = false;
                }

                // Si 'answers' no contiene elementos
                if (count($question['answers']) === 0) {
                    $thatsRight = false;
                }
                else {
                    foreach ($question['answers'] as $answer) {
                        // Si alguna 'answer' no tiene los elementos 'content', 'value' o 'tolerance'
                        if (!isset($answer['content'], $answer['value'], $answer['tolerance'])) {
                            $thatsRight = false;
                        }
                        else {
                            // Si el elemento 'content' de alguna 'answer' esta vacío
                            if (empty($answer['content'])) {
                                $thatsRight = false;
                            }

                            // Si value o content no son númericos
                            if (!is_numeric($answer['value']) || !is_numeric($answer['tolerance'])) {
                                $thatsRight = false;
                            }
                        }
                    }
                }
            }

            // Si la pregunta tiene algún fallo se elimina del resto del array
            if ($thatsRight === false) {
                unset($questions[$key]);
            }
        }

        return $questions;
    }

    /**
     * Reemplaza los caracteres de escape de Gift por unos marcadores de posición expecificos de esta aplicación, este cambio se realiza antes
     *   del precesado del texto en formato Gift, para que así no intefiera en el mismo.
     *    Las sustituciones son las siguientes:
     *
     *    \~ -> @tilde;
     *    \= -> @equal;
     *    \# -> @sharp;
     *    \{ -> @lbracket;
     *    \} -> @rbracket;
     *    \: -> @colon;
     *    \\ -> @backslash;
     *    \n -> <br />
     *
     * @param  string $text  Texto extraido pero no procesado de un archivo en formato Gift
     * @return string
     */
    private function giftScapeChars2PlaceHolders ($text)
    {
        $giftScapeChars = array ('\\~',     '\\=',     '\\#',     '\\{',        '\\}',        '\\:',     '\\\\',        '\\n');
        $placeHolders   = array ('@tilde;', '@equal;', '@sharp;', '@lbracket;', '@rbracket;', '@colon;', '@backslash;', '<br />');

        $textReplaced = str_replace($giftScapeChars, $placeHolders, $text);

        return $textReplaced;
    }

    /**
     * Reemplaza los marcadores de posición, incluidos por esta aplicación para sustituir los caracteres de escape de Gift, por el texto
     *    que representan dichos marcadores, este cambio se realiza una vez ha terminado el procesado del texto en formato Gift.
     *    Las sustituciones son las siguientes:
     *
     *    @tilde;     -> ~
     *    @equal;     -> =
     *    @sharp;     -> #
     *    @lbracket;  -> {
     *    @rbracket;  -> }
     *    @colon;     -> :
     *    @backslash; -> \
     *
     * @param  string $text  Texto extraido y procesado de un archivo en formato Gift
     * @return string
     */
    private function placeHolders2Char ($text)
    {
        $placeHolders = array ('@tilde;', '@equal;', '@sharp;', '@lbracket;', '@rbracket;', '@colon;', '@backslash;');
        $chars        = array ('~',        '=',      '#',       '{',          '}',          ':',       '\\');

        $textReplaced = str_replace($placeHolders, $chars, $text);

        return $textReplaced;
    }
}