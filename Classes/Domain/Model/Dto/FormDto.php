<?php

namespace Archriss\ArcParsermjml\Domain\Model\Dto;

class FormDto
{

    /**
     * @var string 
     */
    protected $style = '';

    /**
     * @var bool 
     */
    protected $override = false;

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
     * @return bool
     */
    public function isOverride(): bool
    {
        return $this->override;
    }

    /**
     * @param bool $override
     */
    public function setOverride(bool $override): void
    {
        $this->override = $override;
    }

}