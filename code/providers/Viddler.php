<?php

namespace RichardsJoqvist\silverstripeEmbedder;

/**
 * Viddler Embedder provider
 */
class Viddler extends EmbedderProvider implements IEmbedderProvider
{
    // Render embed from Viddler links, the following formats are recognized:
    // http://[www.]viddler.com/v/6d064c53
    protected $pattern = "/([^\"|'|=])(https?:\/\/)(www\.|)(viddler\.com\/v\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU";

    public function render($matches)
    {
        // Render from oEmbed json
        if ($html = $this->embedder->renderHTML('http://www.viddler.com/oembed/?url=http://www.viddler.com/v/' . $matches[5])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
