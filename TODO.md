Add a reset() method to reset the Route, or a specific property.  Cannot reset
the name or path.  Perhaps clear() ?

Add acceptLanguages, acceptCharsets, acceptEncodings ?

Document:

- Custom matching rules

- Customizing the Container

- Extending Map and Route classes

- For serializing, can we just serialize the whole Map? No, because you need to get the Map from the Container, otherwise it won't wire up the the other objects properly.

Change the description from "PSR-7 compliant" to "Router for PSR-7".

Rename "Advanced Topics" titles to "I want to 'x'"

