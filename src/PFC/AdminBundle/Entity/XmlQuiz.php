<?php

/**
*  » Entidad XmlQuiz, que es utilizado como objeto intermedio en la serialización/deserialización de los ficheros, en la importación y exportación
*
* @package PFC\AdminBundle\Entity;
*/

namespace PFC\AdminBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

/** @JMS\XmlRoot("quiz") */
class XmlQuiz
{

	/**
     * @JMS\SerializedName("text")
     * @JMS\Type("string")
     */
	public $text;

    /**
     * ArrayCollection de preguntas
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\XmlQuestion>")
     * @JMS\XmlList(inline = true, entry="question")  //Inline, solo categoriza, no incluye questions como nodo
     */
    public $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }
}

class XmlQuestion
{

    /**
     * Categoría de la pregunta si la tuviera
     * @JMS\SerializedName("category")
     * @JMS\Type("PFC\AdminBundle\Entity\StringClass")
     */
    public $category;

    /**
     * Tipo de la prengunta
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $type;

    /**
     * @JMS\SerializedName("name")
     * @JMS\Type("PFC\AdminBundle\Entity\StringClass")
     */
    public $name;

    /**
     * Texto descriptivo de la pregunta
     * @JMS\SerializedName("questiontext")
     * @JMS\Type("PFC\AdminBundle\Entity\StringClass")
     */
    public $questiontext;

    /**
     * Texto de la retroalimentacion
     * @JMS\SerializedName("generalfeedback")
     * @JMS\Type("PFC\AdminBundle\Entity\StringClass")
     */
    public $generalfeedback;

    /**
     * Puntuación por defecto
     * @JMS\SerializedName("defaultgrade")
     * @JMS\Type("double")
     */
    public $defaultgrade;

    /**
     * Penalizacion por respuesta erronea
     * @JMS\SerializedName("penalty")
     * @JMS\Type("double")
     */
    public $penalty;

    /**
     * Oculta
     * @JMS\SerializedName("hidden")
     * @JMS\Type("integer")
     */
    public $hidden;

    /**
     * Tipo de respuesta, sencilla o múltiple
     * @JMS\SerializedName("single")
     * @JMS\Type("boolean")
     */
    public $single;

    /**
     * ArrayCollection de las respuestas
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\XmlAnswer>")
     * @JMS\XmlList(inline = true, entry="answer")  //Inline, solo categoriza, no incluye answers como nodo
     */
    public $answers;

    public function __construct()
    {
        $this->category = new StringClass();
        $this->name = new StringClass();
        $this->questiontext = new StringClass();
        $this->generalfeedback = new StringClass();
        $this->answers = new ArrayCollection();
    }
}


class StringClass
{
    /**
     * @JMS\SerializedName("text")
     * @JMS\Type("string")
     */
    public $text;

    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $format;
}

class XmlAnswer
{
    /**
     * @JMS\XmlAttribute
     * @JMS\Type("integer")
     */
    public $fraction;

    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $format;

    /**
     * Texto redacción de la respuestas
     * @JMS\SerializedName("text")
     * @JMS\Type("string")
     */
    public $text;

    /**
     * Tolerancia
     * @JMS\SerializedName("tolerance")
     * @JMS\Type("double")
     */
    public $tolerance;

    /**
     * Texto de la retroalimentación
     * @JMS\SerializedName("feedback")
     * @JMS\Type("PFC\AdminBundle\Entity\StringClass")
     */
    public $feedback;

    public function __construct()
    {
        $this->feedback = new StringClass();
    }
}