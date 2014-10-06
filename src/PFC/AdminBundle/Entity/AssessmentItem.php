<?php

/**
*  » Entidad AssessmentItem
*    ┌─────────────────────────────────────────────────────────────────────────┐
*      - @version 1.0 @date 2014-7-22
*    └─────────────────────────────────────────────────────────────────────────┘
*/

namespace PFC\AdminBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

/*
 *    ┌─────────────────────────────────────────────────────────────────────────┐
 *      Jerarquía de un AssessmentItem XML de IMS QTI:
 *
 *         AssessmentItem*
 *          \_ ResponseDeclaration (Maybe several)
 *          |   \_ CorrectResponse
 *          |   |   \_Value (Maybe several)
 *          |   \_ Mapping
 *          |       \_ MapEntry (Maybe several)
 *          \_ ItemBody
 *              \_ ChoiceInteraction (Maybe several)
 *              |   \_ Prompt
 *              |   \_ SimpleChoice (Maybe several)
 *              \_ ExtendedTextInteraction (Maybe several)
 *                  \_ Prompt
 *
 *      * contiene otros elementos y subelementos no usados en esta aplicación
 *    └─────────────────────────────────────────────────────────────────────────┘
 */

/** @JMS\XmlRoot("assessmentItem") */
class AssessmentItem
{

    /*
     * Constante con todas las etiquetas del XML
     */
    const TAGS = '<assessmentItem><responseDeclaration><correctResponse><value><mapping><mapEntry><itemBody><choiceInteraction><extendedTextInteraction><prompt><simpleChoice><feedbackInline><br><m_math><m_annotation>';
    //<br><m:math><m:annotation>

    /**
     * Referencia al fichero XML que es serializado en esta clase
     * @JMS\Type("string")
     */
    public $ref;

    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("title")
     * @JMS\Type("string")
     */
    public $title;

    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("adaptive")
     * @JMS\Type("string")
     */
    public $adaptive;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\ResponseDeclaration>")
     * @JMS\XmlList(inline = true, entry="responseDeclaration") //Inline, solo categoriza, no incluye items como nodo
     */
    public $responseDeclaration;

    /**
     * @JMS\SerializedName("itemBody")
     * @JMS\Type("PFC\AdminBundle\Entity\ItemBody")
     */
    public $itemBody;

    public function __construct()
    {
        $this->responseDeclaration = new ArrayCollection();
        $this->itemBody = new ItemBody();
    }
}

class ResponseDeclaration
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("identifier")
     * @JMS\Type("string")
     */
    public $identifier;

    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("cardinality")
     * @JMS\Type("string")
     */
    public $cardinality;

    /**
     * @JMS\SerializedName("correctResponse")
     * @JMS\Type("PFC\AdminBundle\Entity\CorrectResponse")
     */
    public $correctResponse;

    /**
     * @JMS\SerializedName("mapping")
     * @JMS\Type("PFC\AdminBundle\Entity\Mapping")
     */
    public $mapping;

    public function __construct()
    {
        $this->correctResponse = new correctResponse();
        $this->mapping = new Mapping();
    }
}

class CorrectResponse
{
    /**
     * @JMS\Type("array<string>")
     * @JMS\XmlList(inline = true, entry="value")
     */
    public $value;
}

class Mapping
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("defaultValue")
     * @JMS\Type("string")
     */
    public $defaultValue;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\MapEntry>")
     * @JMS\XmlList(inline = true, entry="mapEntry")
     */
    public $mapEntry;

    public function __construct()
    {
        $this->mapEntry = new ArrayCollection();
    }
}

class MapEntry
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("mapKey")
     * @JMS\Type("string")
     */
    public $mapKey;

    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("mappedValue")
     * @JMS\Type("string")
     */
    public $mappedValue;
}

Class ItemBody
{
    /**
     * @JMS\XmlValue
     * @JMS\Type("string")
     */
    public $text;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\ChoiceInteraction>")
     * @JMS\XmlList(inline = true, entry="choiceInteraction")
     */
    public $choiceInteraction;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\ExtendedTextInteraction>")
     * @JMS\XmlList(inline = true, entry="extendedTextInteraction")
     */
    public $extendedTextInteraction;

    public function __construct()
    {
        $this->choiceInteraction = new ArrayCollection();
        $this->extendedTextInteraction = new ArrayCollection();
    }
}

Class ChoiceInteraction
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("responseIdentifier")
     * @JMS\Type("string")
     */
    public $responseIdentifier;

    /**
     * Texto de la pregunta
     *
     * @JMS\SerializedName("prompt")
     * @JMS\Type("string")
     */
    public $prompt;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\SimpleChoice>")
     * @JMS\XmlList(inline = true, entry="simpleChoice")
     */
    public $simpleChoice;

    public function __construct()
    {
        $this->simpleChoice = new ArrayCollection();
    }
}

Class SimpleChoice
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("identifier")
     * @JMS\Type("string")
     */
    public $identifier;

    /**
     * @JMS\XmlValue
     * @JMS\Type("string")
     */
    public $value;

}

Class ExtendedTextInteraction
{
    /**
     * @JMS\XmlAttribute
     * @JMS\SerializedName("responseIdentifier")
     * @JMS\Type("string")
     */
    public $responseIdentifier;

    /**
     * Texto de la pregunta
     *
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\Prompt>")
     * @JMS\XmlList(inline = true, entry="prompt")
     */
    public $prompt;
}

Class Prompt
{
    /**
     * @JMS\XmlValue
     * @JMS\Type("string")
     */
    public $text;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\Math>")
     * @JMS\XmlList(inline = true, entry="m_math")
     */
    public $math;

    public function __construct()
    {
        $this->math = new ArrayCollection();
    }
}

Class Math
{
    /**
     * @JMS\XmlValue
     * @JMS\Type("string")
     */
    public $text;

    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\Annotation>")
     * @JMS\XmlList(inline = true, entry="m_annotation")
     */
    public $annotation;

    public function __construct()
    {
        $this->annotation = new ArrayCollection();
    }
}

Class Annotation
{
    /**
     * @JMS\XmlValue
     * @JMS\Type("string")
     */
    public $text;
}