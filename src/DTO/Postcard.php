<?php

namespace Nexibi\PrintOne\DTO;

class Postcard
{
    public string $format;

    public function __construct(public Template $front, public Template $back)
    {
        if ($this->front->format !== $this->back->format) {
            throw new \InvalidArgumentException('Front and back template should be of the same format');
        }
        $this->format = $this->front->format;
    }
}
