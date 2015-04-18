Add a reset() method to reset the Route, or a specific property.  Cannot reset
the name or path.  Perhaps clear() ?

How to check faked method -- middleware to "fix" the request?

Add acceptLanguages, acceptCharsets, acceptEncodings ?

Let accepts() take values like ['.json' => 'application/json'] etc. Hm, or if it starts with a dot, consider it a filename extension.
