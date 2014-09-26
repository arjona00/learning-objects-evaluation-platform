<?php
namespace PFC\ModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * » Entidad PageUrl
 *
 * @ORM\Table(name="PagesUrl", indexes = {@ORM\Index(name="IDX_LANG", columns={"lang"})})
 * @ORM\Entity
 */
class PageUrl
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=2)
     */
    private $lang;

    /**
     * @ORM\ManyToOne(targetEntity="PFC\ModelBundle\Entity\Page")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id")
     */
    protected $page;

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
     * Set url
     *
     * @param string $url
     * @return PageUrl
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set lang
     *
     * @param string $lang
     * @return PageUrl
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set page
     *
     * @param \PFC\ModelBundle\Entity\Page $page
     * @return PageUrl
     */
    public function setPage(\PFC\ModelBundle\Entity\Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return \PFC\ModelBundle\Entity\Page
     */
    public function getPage()
    {
        return $this->page;
    }
}