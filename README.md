# YSE Jumper

Support for jump nav using paragraphs.  Looks for the presence of a the 
'field_jump_nav_heading' field.   If we do not find a string in that field, 
we use the first group of the uuid prefixed with the string 'jump_'.  We use
uuid in case we expect a URL to work on different sites via Entity Share. If
a paragraph is imported using ome other method, this value might not travel!
We prefix the calculated anchor so we do not run afoul of the HTML standard,
which does not want leading numerals in a DOM id.

## Tokens

* [jumper:hex] 'jump_' . first group of the paragraph's uuid.
* [jumper:slug] content of field_jump_nav_heading slugified by Html::getId
* [jumper:anchor] either slug or hex, with poundsign
* [jumper:nodepath] if found, the canonical path to final node parent

## preprocess_paragraphs

We look for the field_jump_nav_heading field, and then place the hex or slug 
as above into the attributes array as 'id' for DOM id.

## Config

The field name of 'field_jump_nav_heading' is hardcoded as of now.

To cut down a little on pathseeking, we keep an array of node bundles in the config 
under 'selected_nodebundles'

Right now we are running under the assumption that a paragraph needs the field
'field_jump_nav_heading' to be present the bundle fieldlist for the path token 
lookup and value copy. In the future we will add a bundle selection form.