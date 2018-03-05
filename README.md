## Embedder SilverStripe module

Embedder enables embedding in content fields area similar to how embeds work in WordPress, and can embed media from
pasted links and HTML code.

## Requirements

*   SilverStripe 4

## Installation

Drop the module into your SilverStripe project and run /dev/build

## Usage

The idea is to convert URLS for media ( Youtube, Vimeo) in the markup into embbedded media.
For example the following text

    <p> Best Video ever !</p>
    <p>https://www.youtube.com/watch?v=9bZkp7q19f0</p>

would be converted into

    <p> Best Video ever !</p>
    <p><div class="fluid-width-video-wrapper" style="padding-top: 56.2667%;"><iframe src="https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen="" name="fitvid2"></iframe></div></p>

### Enable Embedder on content fields

Embedder must be activated in your project \_config.php. This is done by registering which fields on which pagetypes
Embedder should work.
The following example would enable Embedder on the "Content" field on pages of the "Page" type:

    RichardsJoqvist\silverstripeEmbedder\Embedder:
      fields:
        Page:
          - Content

The following example would enable Embedder on the "Intro" field on all page types:

    RichardsJoqvist\silverstripeEmbedder\Embedder:
      fields:
        all
          - Intro;

### Specify embedded media size

Add the following to set the width of media embedded from links:

    RichardsJoqvist\silverstripeEmbedder\Embedder:
      width: 750
      maxWidth: 750

The following methods are included to control embed size:

*   setWidth([int])
*   setHeight([int])
*   setMaxWidth([int])
*   setMaxHeight([int])

Sizes will be calculated with aspect ratio maintained.

### Register media providers

Several pre-packaged providers (content sites) are included. They must be separately registered as such:

    RichardsJoqvist\silverstripeEmbedder\Embedder:
      providers:
        - \RichardsJoqvist\silverstripeEmbedder\Youtube
        - \RichardsJoqvist\silverstripeEmbedder\Vimeo
        - \RichardsJoqvist\silverstripeEmbedder\Instagram

The following providers are included:

*   Flickr
*   Instagram
*   Viddler
*   Vimeo
*   Youtube

You can easily add more custom providers that conform to the oEmbed specification. Simply copy one of the included
provider classes to your project directory and modify it to suit your needs, then run /dev/build to register the
provider class with SilverStripe and enable it in your \_config.php. See the [oEmbed website](http://oembed.com/)
for more information.

### Register HTML tags

Embedder can also render pasted HTML code based on tag names:

    RichardsJoqvist\silverstripeEmbedder\Embedder:
      tags:
        - object
        - iframe
        - embed

This registers &lt;object&gt;, &lt;iframe&gt; and &lt;embed&gt;-tags to be rendered as objects instead of HTML entities.
For example, if the following code is pasted in an Embedder-enabled field it will be rendered as an iframe:

    <iframe width="640" height="390"
    src="http://www.youtube.com/embed/dQw4w9WgXcQ"
    frameborder="0" allowfullscreen></iframe>

### Full config Example

This file would be called embedder.yml

    RichardsJoqvist\silverstripeEmbedder\Embedder:
    width: 750
    maxWidth: 750
    fields:
      SilverStripe\Blog\Model\BlogPost:
        - Content
      Page:
        - Content
      AnotherPageType:
        - Content
        - Another HTML Field
    providers:
      - \RichardsJoqvist\silverstripeEmbedder\Youtube
      - \RichardsJoqvist\silverstripeEmbedder\Vimeo
      - \RichardsJoqvist\silverstripeEmbedder\Instagram
