<?php

namespace PFC\ModelBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use PFC\ModelBundle\Entity\Subject;
use PFC\ModelBundle\Entity\Category;
use PFC\ModelBundle\Entity\Subcategory;
use PFC\ModelBundle\Entity\Question;
use PFC\ModelBundle\Entity\Answer;


/**
*  » Data Fixtures que crea las asignaturas, categorías, y subcategorías de prueba, y relación entre ellas
*
*/
class LoadSubjectsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        // Listado de asiganturas (subjects), con sus categorías (categories), subcategorías (subcategories), preguntas (questions) y respuestas (answers)
        $question_types = array (
                            'multichoice' => 0,
                            'truefalse' => 1,
                            'shortanswer' => 2,
                            'matching' => 3,
                            'cloze'=> 4,
                            'essay' => 5,
                            'numerical' => 6,
                            'description'=> -1,
                            'category' => -1
                            );

        $arrayQuestions =  array (
                               array(
                                        'title'       => "Responda a la siguiente afirmación",
                                        'description' => "¿Cuántas patas tiene el gato?",
                                        'type'        => $question_types['multichoice'],
                                        'single'      => true,  //indica si la respuesta permite marcar más de una
                                        'penalty'    => 0.0,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "4",
                                                'value'    => 100.0
                                                 ),
                                            array(
                                                'content'  => "3",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "1",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "2",
                                                'value'    => 0.0
                                                 )
                                         )
                                    ),
                               array(
                                        'title'       => "Responda a la siguiente afirmación",
                                        'description' => "¿Que parte de las abejas es la ponzoña?",
                                        'type'        => $question_types['multichoice'],
                                        'single'      => true,
                                        'penalty'    => 0.0,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "Cabeza",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "Aguijón",
                                                'value'    => 100.0
                                                 ),
                                            array(
                                                'content'  => "Patas",
                                                'value'    => 0.0
                                                 )
                                         )
                                    ),
                               array(
                                        'title' => "Responda a la siguiente afirmación",
                                        'description' => "¿Cómo es el cielo de marte?",
                                        'type' =>  $question_types['multichoice'],
                                        'single'      => true,
                                        'penalty'    => 0.0,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "verde",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "rojo",
                                                'value'    => 100.0
                                                 ),
                                            array(
                                                'content'  => "azul",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "amarillo",
                                                'value'    => 0.0
                                                 )
                                         )
                                    ),
                                array(
                                        'title'       => "Responda a la siguiente afirmación ",
                                        'description' => "¿La tierra es redonda?",
                                        'type'        =>  $question_types['truefalse'],
                                        'single'      => true,
                                        'penalty'    => 100.0,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "true",
                                                'value'    => 100.0
                                                 ),
                                            array(
                                                'content'  => "false",
                                                'value'    => 0.0
                                                 )
                                         )
                                    ),
                               array(
                                        'title'       => "Responda a la siguiente afirmación ",
                                        'description' => "¿Cúal es la puntuación máxima que puedes conseguir tirando seis dados normales?",
                                        'type'        =>  $question_types['multichoice'],
                                        'single'      => true,
                                        'penalty'    => 0.0,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "32",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "25",
                                                'value'    => 0.0
                                                 ),
                                            array(
                                                'content'  => "36",
                                                'value'    => 100.0
                                                 )
                                         )
                                    ),
                                array(
                                        'title'       => "Calcula el resultado de la siguiente formula  ",
                                        'description' => "$$ \sqrt{16} $$",
                                        'type'        =>  $question_types['numerical'],
                                        'single'      => true,
                                        'penalty'    => 33.33,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "4",
                                                'value'    => 100.0,
                                                'tolerance' => 0.1
                                                 ),
                                            array(
                                                'content'  => "5",
                                                'value'    => 100.0,
                                                'tolerance' => 0.5
                                                 )
                                         )
                                    ),
                                array(
                                        'title'       => "Responda a la siguiente afirmación con seleccionando las respuestas que considere correctas",
                                        'description' => "¿Cuál es la composición del aire?",
                                        'type'        =>  $question_types['multichoice'],
                                        'single'      => false,
                                        'penalty'    => 33.33,
                                        'level'       => 5.0,
                                        'answers'     =>
                                        array
                                         (
                                            array(
                                                'content'  => "Oxígeno",
                                                'value'    => 25.0
                                                 ),
                                            array(
                                                'content'  => "Nitrógeno",
                                                'value'    => 25.0
                                                 ),
                                            array(
                                                'content'  => "Dióxido de carbono",
                                                'value'    => 25.0
                                                 ),
                                            array(
                                                'content'  => "Gases inertes como argón y neón",
                                                'value'    => 25.0
                                                 ),
                                            array(
                                                'content'  => "Cloro",
                                                'value'    => 0
                                                 ),
                                            array(
                                                'content'  => "Azufre",
                                                'value'    => 0
                                                 ),
                                            array(
                                                'content'  => "Zinc",
                                                'value'    => 0
                                                 )
                                         )
                                    )
                            );

        $arraySubjects = array (
                                   array (
                                   'name' => "MATEMÁTICA DISCRETA",
                                   'description' => "1 Praesent at tellus porttitor nisl porttitor sagittis. Mauris in massa ligula, a tempor nulla. Ut tempus interdum mauris vel vehicula. Nulla ullamcorper tortor commodo in sagittis est accumsan"
                                   ),
                                    array (
                                   'name' => "MÉTODOS NUMÉRICOS",
                                   'description' => "2 Praesent at tellus porttitor nisl porttitor sagittis. Mauris in massa ligula, a tempor nulla. Ut tempus interdum mauris vel vehicula. Nulla ullamcorper tortor commodo in sagittis est accumsan"
                                   ),
                                    array (
                                   'name' => "ESTADÍSTICA Y PROBABILIDAD I",
                                   'description' => "3 Praesent at tellus porttitor nisl porttitor sagittis. Mauris in massa ligula, a tempor nulla. Ut tempus interdum mauris vel vehicula. Nulla ullamcorper tortor commodo in sagittis est accumsan"
                                   ),
                                    array (
                                   'name' => "ESTADÍSTICA Y PROBABILIDAD II",
                                   'description' => "4 Praesent at tellus porttitor nisl porttitor sagittis. Mauris in massa ligula, a tempor nulla. Ut tempus interdum mauris vel vehicula. Nulla ullamcorper tortor commodo in sagittis est accumsan"
                                   ),
                                    array (
                                   'name' => "PROGRAMACIÓN MATEMÁTICA Y TÉCNICAS DE OPTIMIZACIÓN",
                                   'description' => "5 Praesent at tellus porttitor nisl porttitor sagittis. Mauris in massa ligula, a tempor nulla. Ut tempus interdum mauris vel vehicula. Nulla ullamcorper tortor commodo in sagittis est accumsan"
                                   )
                                );

        $arraySubjectCategories   =  array (
                                            "MATEMÁTICA DISCRETA" => array
                                                (
                                                array(
                                                    'name'        => "Tema 1",
                                                    'title'       => "Introducción",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "1"
                                                    ),
                                                array(
                                                    'name'        => "Tema 2",
                                                    'title'       => "Lógica y conjuntos",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "2"
                                                    )
                                                ),
                                            "MÉTODOS NUMÉRICOS" => array
                                                (
                                                array(
                                                    'name'        => "Tema 1",
                                                    'title'       => "Metodos de solucion de ecuaciones",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "3"
                                                    ),
                                                array(
                                                    'name'        => "Tema 2",
                                                    'title'       => "Metodos de solucion de sistemas de ecuaciones",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "4"
                                                    ),
                                                array(
                                                    'name'        => "Tema 3",
                                                    'title'       => "Diferenciacion e integracion numérica",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "5"
                                                    ),
                                                array(
                                                    'name'        => "Tema 4",
                                                    'title'       => "Solución de ecuaciones diferenciales",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "6"
                                                    )
                                                ),
                                            "ESTADÍSTICA Y PROBABILIDAD I" => array
                                                (
                                                array(
                                                    'name'        => "Tema 2",
                                                    'title'       => "Conjuntos y técnicas de conteo",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "7"
                                                    ),
                                                array(
                                                    'name'        => "Tema 5",
                                                    'title'       => "Axiomas y teoremas I",
                                                    'description' => "3 porttitor nisl sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "8"
                                                    ),
                                                array(
                                                    'name'        => "Tema 5",
                                                    'title'       => "Axiomas y teoremas II",
                                                    'description' => "4 porttitor nisl sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "9"
                                                    ),
                                                array(
                                                    'name'        => "Tema 7",
                                                    'title'       => "Teorema de Bayes",
                                                    'description' => "5 porttitor nisl sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "10"
                                                    )
                                                ),
                                            "ESTADÍSTICA Y PROBABILIDAD II" => array
                                                (
                                                array(
                                                    'name'        => "Tema 6",
                                                    'title'       => "Espacio muestral y eventos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "11"
                                                    ),
                                                array(
                                                    'name'        => "Tema 9",
                                                    'title'       => "Espacio finito equiprobable",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris ligula",
                                                    'id_temp'     => "12"
                                                    )
                                                ),
                                            "PROGRAMACIÓN MATEMÁTICA Y TÉCNICAS DE OPTIMIZACIÓN" => array
                                                (
                                                 array(
                                                    'name'        => "Tema 6",
                                                    'title'       => "Conjuntos convexos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "13"
                                                    ),
                                                array(
                                                    'name'        => "Tema 8",
                                                    'title'       => "Reglas de branching para algoritmos branch-and-cut",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula",
                                                    'id_temp'     => "14"
                                                    )
                                                )
                                            );

        $arrayCategorySubcategories = array (
                                            "1" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 1",
                                                    'title'       => "Introducción",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "Lógica",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 3",
                                                    'title'       => "Conjuntos",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "2" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 1",
                                                    'title'       => "Metodos de solucion de ecuaciones",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "Metodos de solucion de sistemas de ecuaciones",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 3",
                                                    'title'       => "Diferenciacion e integracion numérica",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 4",
                                                    'title'       => "Solución de ecuaciones diferenciales",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "3" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "Conjuntos y técnicas de conteo",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 5",
                                                    'title'       => "Axiomas",
                                                    'description' => "3 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "Teoremas II",
                                                    'description' => "4 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 7",
                                                    'title'       => "Teorema de Bayes",
                                                    'description' => "5 porttitor nisl sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "4" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "Espacio muestral y eventos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 9",
                                                    'title'       => "Espacio finito equiprobable",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris ligula"
                                                    )
                                                ),
                                            "5" => array
                                                (
                                                 array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "porttitor convexos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 8",
                                                    'title'       => "Mauris de branching para algoritmos branch-and-cut",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "6" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 1",
                                                    'title'       => "ligula",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "ligula 3",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 3",
                                                    'title'       => "ligula 5",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "7" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 1",
                                                    'title'       => "sagittis de solucion de ecuaciones",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "sagittis de solucion de sistemas de ecuaciones",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 3",
                                                    'title'       => "sagittis e integracion numérica",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 4",
                                                    'title'       => "sagittis de ecuaciones diferenciales",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "8" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "sagittis y técnicas de conteo",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 5",
                                                    'title'       => "sagittis",
                                                    'description' => "3 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "sagittis II",
                                                    'description' => "4 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 7",
                                                    'title'       => "sagittis de Bayes",
                                                    'description' => "5 porttitor nisl sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "9" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "porttitor muestral y eventos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 9",
                                                    'title'       => "porttitor finito equiprobable",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris ligula"
                                                    )
                                                ),
                                            "10" => array
                                                (
                                                 array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "nisl convexos",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 8",
                                                    'title'       => "nisl de branching para algoritmos branch-and-cut",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "11" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 1",
                                                    'title'       => "sagittis de massa de ecuaciones",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "sagittis de massa de sistemas de ecuaciones",
                                                    'description' => "2 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 3",
                                                    'title'       => "sagittis e massa numérica",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 4",
                                                    'title'       => "sagittis de massa diferenciales",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "12" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 2",
                                                    'title'       => "sagittis y massa de conteo",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 5",
                                                    'title'       => "sagittis massa",
                                                    'description' => "3 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "sagittis II massa",
                                                    'description' => "4 porttitor nisl sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 7",
                                                    'title'       => "sagittis de Bayes massa",
                                                    'description' => "5 porttitor nisl sagittis. Mauris in massa ligula"
                                                    )
                                                ),
                                            "13" => array
                                                (
                                                array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "porttitor muestral y eventos Mauris",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 9",
                                                    'title'       => "porttitor finito equiprobable Mauris",
                                                    'description' => "4 porttitor nisl porttitor sagittis. Mauris ligula"
                                                    )
                                                ),
                                            "14" => array
                                                (
                                                 array(
                                                    'name'        => "Subcategoria 6",
                                                    'title'       => "nisl convexos nisl",
                                                    'description' => "1 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    ),
                                                array(
                                                    'name'        => "Subcategoria 8",
                                                    'title'       => "nisl de branching para algoritmos nisl branch-and-cut",
                                                    'description' => "3 porttitor nisl porttitor sagittis. Mauris in massa ligula"
                                                    )
                                                )
                                            );

        if(!empty($arraySubjects)){
            foreach ($arraySubjects as $Subject) {

                // Insertamos la asignatura
                $sub       = new Subject();
                $sub       ->setName($Subject['name']);
                $sub       ->setDescription($Subject['description']);

                $manager        ->persist($sub);
                $manager        ->flush();
                $sub       = ObjectIdentity::fromDomainObject($sub);

                // Obtenemos el identificador de la asignatura y el objeto completo
                $sub       = $manager
                                    ->getRepository('ModelBundle:Subject')
                                    ->find($sub->getIdentifier());

                $arrayCategory = $arraySubjectCategories[$sub->getName()];

                if(!empty($arrayCategory)){
                    foreach ($arrayCategory as $category) {

                        // Insertamos la categoría
                        $Category    = new Category();
                        $Category    ->setName($category['name']);
                        $Category    ->setTitle($category['title']);
                        $Category    ->setDescription($category['description']);
                        $Category    ->setSubject($sub);

                        $manager     ->persist($Category);
                        $manager     ->flush();

                        $Category   = ObjectIdentity::fromDomainObject($Category);

                        $Category   = $manager
                                        ->getRepository('ModelBundle:Category')
                                        ->find($Category->getIdentifier());

                        $arraySubcategory = $arrayCategorySubcategories[$category['id_temp']];

                        if(!empty($arraySubcategory)){
                             foreach ($arraySubcategory as $subcategory) {
                                // Insertamos la subcategoria
                                $Subcategory    = new Subcategory();
                                $Subcategory    ->setName($subcategory['name']);
                                $Subcategory    ->setTitle($subcategory['title']);
                                $Subcategory    ->setDescription($subcategory['description']);
                                $Subcategory    ->setSubject($sub);
                                $Subcategory    ->setCategory($Category);

                                $manager     ->persist($Subcategory);
                                $manager     ->flush();

                                $Subcategory   = ObjectIdentity::fromDomainObject($Subcategory);

                                $Subcategory   = $manager
                                                ->getRepository('ModelBundle:Subcategory')
                                                ->find($Subcategory->getIdentifier());


                                $num_questions = rand(1, 15);
                                //echo $num_questions . ' ';
                                for ($i = 1; $i <= $num_questions; $i++) {
                                    $question = $arrayQuestions[rand(0, count($arrayQuestions) - 1)]; //Cogemos aleatoriamente una de las preguntas

                                    $Question    = new Question();
                                    $Question    ->setTitle($question['title']);
                                    $Question    ->setDescription($question['description']);
                                    $Question    ->setType($question['type']);
                                    $Question    ->setSingle($question['single']);
                                    $Question    ->setPenalty($question['penalty']);
                                    $Question    ->setLevel(rand(0,10));
                                    $Question    ->setSubject($sub);
                                    $Question    ->setCategory($Category);
                                    $Question    ->setSubcategory($Subcategory);
                                    $Question    ->setNumAnswers(0);
                                    $Question    ->setNumCorrectAnswers(0);

                                    $manager     ->persist($Question);

                                    $manager     ->flush();

                                    $Question    = ObjectIdentity::fromDomainObject($Question);

                                    $Question   = $manager
                                                    ->getRepository('ModelBundle:Question')
                                                    ->find($Question->getIdentifier());

                                    $arrayAnswers = $question['answers'];

                                    if(!empty($arrayAnswers)){
                                        foreach ($arrayAnswers as $answer) {
                                            $Answer   = new Answer();
                                            $Answer   ->setContent($answer['content']);
                                            $Answer   ->setValue($answer['value']);
                                            if ($Question->getType() == $question_types['numerical'])
                                                $Answer   ->setTolerance($answer['tolerance']);
                                            else
                                                $Answer   ->setTolerance(0);

                                            $Answer   ->setQuestion($Question);

                                            $manager  ->persist($Answer);
                                        }
                                        $manager     ->flush();
                                    }
                                }

                             }
                         }

                    }

                }
            }
        }
    }

    public function getOrder()
    {
        return 10;
    }
}