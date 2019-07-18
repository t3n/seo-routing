[![CircleCI](https://circleci.com/gh/t3n/seo-routing.svg?style=svg)](https://circleci.com/gh/t3n/seo-routing) [![Latest Stable Version](https://poser.pugx.org/t3n/seo-routing/v/stable)](https://packagist.org/packages/t3n/seo-routing) [![Total Downloads](https://poser.pugx.org/t3n/seo-routing/downloads)](https://packagist.org/packages/t3n/seo-routing) [![License](https://poser.pugx.org/t3n/seo-routing/license)](https://packagist.org/packages/t3n/seo-routing)

# t3n.SEO.Routing

This package has 2 main features:
- **trailingSlash**: ensure that all links ends with a trailing slash (e.g. `example.com/test/ instead of `example.com/test)
- **toLowerCase**: ensure that camelCase links gets redirected to lowercase (e.g. `exmaple.com/lowercase` instead of `exmaple.com/lowerCase` )

You can de- and activate both of them.

Another small feature is to restrict all _new_ neos pages to have a lowercased `uriPathSegment`. This is done by extending the `NodeTypes.Document.yaml`.

## Installation

Just require it via composer:`

```composer require t3n/seo-routing```

## Configuration

### Standard Configuration

In the standard configuration we have activated the trailingSlash (to redirect all uris without a / at the and to an uri with / at the end) and do all redirects with a 301 http status.

*Note: The lowercase redirect is deactivated by default, cause you have to make sure, that there is no `uriPathSegment`  with camelCase or upperspace letters - this would lead to redirects in the neverland.* 

```
t3n:
  SEO:
    Routing:
      redirect:
        enable:
          trailingSlash: true
          toLowerCase: false
        statusCode: 301
      blacklist:
        '/neos/.*': true
```

### Blacklist for redirects

By default, all `/neos/` URLs are ignored for redirects. You can extend the blacklist array with regex as you like:

```yaml
t3n:
  SEO:
    Routing:
      #redirect:
        #...
      blacklist:
        '/neos/.*': true
```