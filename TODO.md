- make generate() use ArrayObject in the callable, like is_match, and not
  return a replacement $data array?

- put addTokens(), etc, into Router. Then Route and Router have the same set
  of spec-related methods. Make into a Trait and force 5.4, or make into
  Abstract and leave 5.3?

