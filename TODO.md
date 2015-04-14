- actually convert to PSR-7

- Can we avoid AbstractSpec?

- get rid of IsServerMatch favor of header matching

- need a way for route to specify what methods to use for matching, so that
  extended objects don't need to hook in to the matcher

- get rid of ArrayObject in Route/Matcher/etc