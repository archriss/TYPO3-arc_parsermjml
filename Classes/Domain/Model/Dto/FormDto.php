<?php

namespace Archriss\ArcParsermjml\Domain\Model\Dto;

class FormDto
{

    /**
     * @var string 
     */
    protected $style = '';

    /**
     * @var array
     */
    protected $override = [];

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    /**
     * @return array
     */
    public function getOverride(): array
    {
        return $this->override;
    }

    /**
     * @param array $override
     */
    public function setOverride(array $override): void
    {
        $this->override = $override;
    }

}