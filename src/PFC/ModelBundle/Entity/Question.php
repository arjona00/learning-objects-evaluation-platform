<?php
namespace PFC\ModelBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * » Entidad Question
 *
 * @ORM\Table(name="Questions")
 * @ORM\Entity
 * @UniqueEntity("id")
 * @ORM\Entity(repositoryClass="PFC\ModelBundle\Repositories\QuestionRepository")
 */
class Question
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
     * @var string
     * @ORM\Column(name="title", type="text")
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * Tipo de la pregunta.
     * @var integer
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * Indica si la respuesta es sencilla o no
     * @var boolean
     * @ORM\Column(name="single", type="boolean")
     */
    private $single;

    /**
     * Penalización por respuesta incorrecta.
     * @var float
     * @ORM\Column(name="penalty", type="float", options={"default" = 0})
     */
    private $penalty;

    /**
     * Indica el nivel de la pregunta de 0 a 10
     * @var float
     * @ORM\Column(name="level", type="float")
     */
    private $level;

    /**
     * Número de veces respondida.
     * @var integer
     * @ORM\Column(name="numAnswers", type="integer", nullable=true, options={"default" = 0})
     */
    private $numAnswers;

    /**
     * Número de veces correctamente respondida.
     * @var integer
     * @ORM\Column(name="numCorrectAnswers", type="integer", nullable=true, options={"default" = 0})
     */
    private $numCorrectAnswers;

    /**
     * @ORM\ManyToOne(targetEntity="PFC\ModelBundle\Entity\Subject")
     * @ORM\JoinColumn(name="subject_id", referencedColumnName="id")
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="PFC\ModelBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="PFC\ModelBundle\Entity\Subcategory")
     * @ORM\JoinColumn(name="subcategory_id", referencedColumnName="id")
     */
    protected $subcategory;

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
     * Set title
     *
     * @param string $title
     * @return Question
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Question
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Question
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set subject
     *
     * @param \PFC\ModelBundle\Entity\Subject $subject
     * @return Question
     */
    public function setSubject(\PFC\ModelBundle\Entity\Subject $subject = null)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return \PFC\ModelBundle\Entity\Subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set category
     *
     * @param \PFC\ModelBundle\Entity\Category $category
     * @return Question
     */
    public function setCategory(\PFC\ModelBundle\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \PFC\ModelBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set level
     *
     * @param float $level
     * @return Question
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return float
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set numAnswers
     *
     * @param integer $numAnswers
     * @return Question
     */
    public function setNumAnswers($numAnswers)
    {
        $this->numAnswers = $numAnswers;

        return $this;
    }

    /**
     * Get numAnswers
     *
     * @return integer
     */
    public function getNumAnswers()
    {
        return $this->numAnswers;
    }

    /**
     * Set numCorrectAnswers
     *
     * @param integer $numCorrectAnswers
     * @return Question
     */
    public function setNumCorrectAnswers($numCorrectAnswers)
    {
        $this->numCorrectAnswers = $numCorrectAnswers;

        return $this;
    }

    /**
     * Get numCorrectAnswers
     *
     * @return integer
     */
    public function getNumCorrectAnswers()
    {
        return $this->numCorrectAnswers;
    }

    /**
     * Set subcategory
     *
     * @param \PFC\ModelBundle\Entity\Subcategory $subcategory
     * @return Question
     */
    public function setSubcategory(\PFC\ModelBundle\Entity\Subcategory $subcategory = null)
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    /**
     * Get subcategory
     *
     * @return \PFC\ModelBundle\Entity\Subcategory
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    /**
     * Set single
     *
     * @param boolean $single
     * @return Question
     */
    public function setSingle($single)
    {
        $this->single = $single;

        return $this;
    }

    /**
     * Get single
     *
     * @return boolean
     */
    public function getSingle()
    {
        return $this->single;
    }

    /**
     * Set penalty
     *
     * @param float $penalty
     * @return Question
     */
    public function setPenalty($penalty)
    {
        $this->penalty = $penalty;

        return $this;
    }

    /**
     * Get penalty
     *
     * @return float
     */
    public function getPenalty()
    {
        return $this->penalty;
    }
}