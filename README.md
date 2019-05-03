[![CircleCI](https://circleci.com/gh/t3n/seo-routing.svg?style=svg)](https://circleci.com/gh/t3n/seo-routing) [![Latest Stable Version](https://poser.pugx.org/t3n/seo-routing/v/stable)](https://packagist.org/packages/t3n/seo-routing) [![Total Downloads](https://poser.pugx.org/t3n/seo-routing/downloads)](https://packagist.org/packages/t3n/seo-routing) [![License](https://poser.pugx.org/t3n/seo-routing/license)](https://packagist.org/packages/t3n/seo-routing)

# t3n.SEO.Routing
Package to ensure that all links end with a trailing slash, e.g. `example.com/test/` instead of `example.com/test.

## Configuration

By default, all `/neos/` URLs are ignored. You can extend the blacklist array with regex as you like.

```yaml
t3n:
  SEO:
    Routing:
      redirect:
        enable: true
        statusCode: 303
      blacklist:
        '/neos/.*': true
```