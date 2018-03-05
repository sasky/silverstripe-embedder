<?php

namespace RichardsJoqvist\silverstripeEmbedder;

abstract class EmbedderProvider implements IEmbedderProvider
{
    protected $embedder;
    protected $pattern = '';

    public function __construct(Embedder $embedder)
    {
        $this->embedder = $embedder;
    }

    public function pattern()
    {
        return $this->pattern;
    }

    /*
     * Render
     *
     * @param  array  $matches
     * @return string HTML
     */
    public function render($matches)
    {
        return '';
    }
}
