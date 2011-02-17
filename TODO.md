Todo Items
==========

- Support "static" (generation-only) routes? How to deal with things like 
"http://google.com/?q={:qstr}"? (The path-prefix will screw that up -- maybe
set path_prefix=null directly on the route? Or check for "://" in the path?)  Call the key "match=false" ?

- Allow `get_path` and `is_match` values to be regular callbacks so they can be cached more effectively (closures cannot be serialized or exported).
