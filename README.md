# Ori
Simple Lightweight PHP Framework

I disliked the amount of bloat that the majority of frameworks have. So I created my own. (I know... why reinvent the wheel?)

- Simplistic DB connections 
- Singletons For Utility Classes
- A router which supports variables and redirects for old code.
- A controller system with namespacing.
- Smarty template engine
- Some utility functions.

That's about it...

# Documentation

The wiki should have the bulk of the documentation. However by default Ori runs in "sandbox" mode. That means errors will be displayed.

Should you wish to transition into production mode delete sandbox.json in hidden/config and create the site_error_log found in hidden/sql/site_error_log.sql