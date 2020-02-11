<?php

namespace Charcoal\GoogleStaticMap\Object;

use Charcoal\Config\AbstractEntity;

/**
 * Class StaticMap
 */
class StaticMap extends AbstractEntity
{
    /**
     * @var string
     */
    private $ident;

    /**
     * @var string
     */
    private $url;

    /**
     * Sets data on this entity.
     *
     * @param array $data Key-value array of data to append.
     * @return self
     * @uses   {self::offsetSet()}
     */
    public function setData(array $data)
    {
        if (isset($data['url']) && !isset($data['ident'])) {
            $data['ident'] = sha1($data['url']);
        }

        parent::setData($data);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdent()
    {
        return $this->ident;
    }

    /**
     * @param mixed $ident Ident for StaticMap.
     * @return self
     */
    public function setIdent($ident)
    {
        $this->ident = $ident;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url Url for StaticMap.
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}
