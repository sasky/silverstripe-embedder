<?php

namespace RichardsJoqvist\silverstripeEmbedder;

interface IEmbedderProvider
{
    public function __construct(Embedder $embedder);

    public function pattern();

    public function render($matches);
}