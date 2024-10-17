# YSE Jumper

Support for jump nav using paragraphs.  Looks for the presence of a the
'field_jump_nav_heading' field.   If we do not find a string in that field,
we use the first group of the uuid prefixed with the string 'jump_'.  We use
uuid in case we expect a URL to work on different sites via Entity Share. If
a paragraph is imported using ome other method, this value might not travel!
We prefix the calculated anchor so we do not run afoul of the HTML standard,
which does not want leading numerals in a DOM id.



## Tokens

THESE ARE PHASED OUT in favor of preprocessing variables for twig, as we
don't use the tokens outside of the paragraph context.  If these become
necessary for solr indexing in a token_field_value swap we will restore them.

## Twig variables


    - ['jump-nav-heading'] sanitized text if exists;
      - also stored in ['attributes']['data-jump-nav-heading']
    - ['dom_id'] sanitized text or hex segment, no octothorpe
      - also stored in ['attributes']['id']
    - ['local_path'] is the top level node canonical path
    - ['share_path'] is local_path . '#' . dom_id
    - ['depth'] is the number of nested levels climbed by _find_ceiling()

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

## yse_paragraphs_promoter

This module has awareness of yse_paragraphs_promoter but can run without it.
