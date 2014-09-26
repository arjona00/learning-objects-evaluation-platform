<?php

/**
*  » Entidad XmlmManifest, que es utilizado como objeto intermedio en la importación de paquetes. Se encarga de serializar el manifiesto.
*/

namespace PFC\AdminBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

/** @JMS\XmlRoot("manifest") */
class XmlManifest
{
    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\XmlResources>")
     * @JMS\XmlList(inline = true, entry="resources")
     */
    public $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
    }
}

class XmlResources
{
    /**
     * @JMS\Type("ArrayCollection<PFC\AdminBundle\Entity\XmlResource>")
     * @JMS\XmlList(inline = true, entry="resource")
     */
    public $resource;


    public function __construct()
    {
        $this->resource = new ArrayCollection();
    }
}

class XmlResource {
    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $identifier;

    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $type;

    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $scormTipe;

    /**
     * @JMS\XmlAttribute
     * @JMS\Type("string")
     */
    public $href;
}