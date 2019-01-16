[![Build Status](https://travis-ci.com/yeebase/Yeebase.SEO.Routing.svg?branch=master)](https://travis-ci.com/yeebase/Yeebase.SEO.Routing)

# Yeebase.SEO.Routing
Package to ensure that all links end with a trailing slash, e.g. `example.com/test/` instead of `example.com/test.

## Configuration

By default, all `/neos/` URLs are ignored. You can extend the blacklist array with regex as you like.

```yaml
Yeebase:
  SEO:
    Routing:
      redirect:
        enable: true
        statusCode: 303
      blacklist:
        '/neos/.*': true
```