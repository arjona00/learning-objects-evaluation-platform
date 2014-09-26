<?php

/**
*  » Repositorio QuestionRepository
*/

namespace PFC\ModelBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class QuestionRepository extends EntityRepository
{
    /**
     *  » Obtiene los de niveles disponibles de una categoría de cuestiones
     *
     * @param     string     $category         Categoria
     * @return    array      $levels     Array con el nº de niveles del que existen cuestiones [1,4,8]
     *
     */
    public function getLevelQuestions($category)
    {
        $em     = $this ->getEntityManager();

        $levels = array();

        for ($i = 1; $i <= 10; $i++){
            $query = $em->createQuery(
                'SELECT q
                FROM ModelBundle:Question q
                WHERE q.category = :category AND q.level >= :minlevel AND q.level < :maxlevel
                ORDER BY q.level ASC'
            )->setParameters(array('minlevel' => $i , 'maxlevel' => $i+1, 'category' => $category->getId()));

            $questions = $query->getResult();
            $levels[$i] = count($questions);
        }

        return $levels;
    }

    /**
    *  » Método para que devuleve un array de preguntas, con los filtros indicados
    *
    * @package PFC\ModelBundle\Repositories;
    * @version 1.0
    *
    * @param   $selector        String      Filtro de consulta [subject, category o subcategory]
    * @param   $id              Integer     Id del tipo indicado en selector
    * @param   $level           Integer     Filtro del nivel de preguntas a consultar. Level = 0 indica no discriminar por nivel
    * @param   $numQuestions    String      Número de preguntas a exportar
    * @return  array            Array       Array de preguntas
    *
    */
    public function findByTypeIdLevelCount($selector, $id, $level = 0, $numQuestions = '')
    {
        $em     = $this ->getEntityManager();

        $selector = strtolower($selector);

        $arrayParameters = array();
        $arrayParameters[$selector]  =  $id;

        $conditionLevel = '';

        $entityObject = 'q.' . $selector. ' = :' . $selector;

        if ($level != 0 ){
            $arrayParameters['minlevel'] =  $level - 1;
            $arrayParameters['maxlevel'] =  $level;
            if ($level == 1)
                $conditionLevel = ' AND q.level >= :minlevel AND q.level <= :maxlevel '; //[0..1]
            else
                $conditionLevel = ' AND q.level > :minlevel AND q.level <= :maxlevel ';  //(1..2](2..3]...(9..10]
        }

        $query = $em->createQuery(
            'SELECT q
            FROM ModelBundle:Question q
            WHERE '. $entityObject . $conditionLevel
        )->setParameters($arrayParameters);

        if($numQuestions != '')
            if (is_numeric($numQuestions ))
                $query->setMaxResults($numQuestions);

        $questions = $query->getResult();


        return $questions;
    }

}