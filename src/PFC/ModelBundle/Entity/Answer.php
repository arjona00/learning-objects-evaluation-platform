<?php
namespace PFC\ModelBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * » Entidad Answers
 *
 * @ORM\Table(name="Answers")
 * @ORM\Entity
 * @UniqueEntity("id")
 */
class Answer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Texto con el contenido de las respuestas
     * @var string
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * Valor de la respuesta.
     * @var float
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * Tolerancia.
     * @var float
     * @ORM\Column(name="tolerance", type="float")
     */
    private $tolerance;

    /**
     * Enlace a la pregunta relacionada.
     * @ORM\ManyToOne(targetEntity="PFC\ModelBundle\Entity\Question")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id")
     */
    protected $question;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Answer
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return Answer
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set tolerance
     *
     * @param float $tolerance
     * @return Answer
     */
    public function setTolerance($value)
    {
        $this->tolerance = $value;

        return $this;
    }

    /**
     * Get tolerance
     *
     * @return float
     */
    public function getTolerance()
    {
        return $this->tolerance;
    }

    /**
     * Set question
     *
     * @param \PFC\ModelBundle\Entity\Question $question
     * @return Answer
     */
    public function setQuestion(\PFC\ModelBundle\Entity\Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return \PFC\ModelBundle\Entity\Question
     */
    public function getQuestion()
    {
        return $this->question;
    }
}