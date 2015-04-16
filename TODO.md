- get rid of IsServerMatch favor of header matching

- Add cookie, attribute, and host matching

- Instead of capturing all values as "attributes", capture separately as
  path/header/cookie values? Then let the user mix & match into request as
  preferred.
