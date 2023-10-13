<?php

namespace App\Entity;

use App\Repository\UrlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UrlRepository::class)
 */
class Url
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=4)
     */
    private $status;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $positions = [];

    public function __construct(string $urlSlug, string $status)
    {
        $this->slug = $urlSlug;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getPositions(): ?array
    {
        return $this->positions;
    }

    public function setPositions(?array $positions): self
    {
        $this->positions = $positions;

        return $this;
    }

    /**
     * Exporte l'url sous la forme d'une ligne de tableau html
     *
     * @return string
     */
    public function exportAsHtmlTableRow():string
    {
        $export = "<TR>\n";
        $export .= "<TD rowspan='".$this->getRowSpan()."'>{$this->getSlug()}</TD>\n";
        $export .= "<TD rowspan='".$this->getRowSpan()."'>{$this->getStatus()}</TD>\n";
        $export.= "</TR>\n";
        foreach ($this->getPositions() as $aPosition) {
            $export .= "<TR>\n";
            $export .= "<TD>{$aPosition['expression']}</TD>\n";
            $export .= "<TD>{$aPosition['position']}</TD>\n";
            $export .= "</TR>\n";
        }
        return $export;
    }

    /**
     * Retourne le nombre de ligne que doit prendre le rowspan
     *
     * @return integer
     */
    protected function getRowSpan():int
    {
        return count($this->getPositions())+1;
    }
}
