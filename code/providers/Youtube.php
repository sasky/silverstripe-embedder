<?php

namespace RichardsJoqvist\silverstripeEmbedder;

/**
 * Youtube Embedder provider
 */
class Youtube extends EmbedderProvider implements IEmbedderProvider
{
    // Render embed from Youtube links, the following formats are recognized:
    // http://[www.]youtube.com/v/dQw4w9WgXcQ
    // http://[www.]youtube.com/watch?v=dQw4w9WgXcQ

    protected $pattern = "/([^\"|'|=])(https?:\/\/)(www\.|)(youtube\.com\/)(watch\?|)(v)(=|\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU";

    public function render($matches)
    {
        // Render from oEmbed json
        if ($html = $this->embedder->renderHTML('http://www.youtube.com/oembed?scheme=https&url=http%3A//www.youtube.com/watch%3Fv%3D' . $matches[8])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
