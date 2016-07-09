Overview
--------

This script is an example of how to create an automated phone tree with a randomized order of menu options.

This is not your typical phone tree with "Listen closely as our menu options may have changed" - no, this phone tree guarantees that the menu options have changed since the previous call.

There are 10 different menu options, which are read off in a random order. This order is saved in a MySQL database to ensure that this order is not repeated for the next call.

When the user enters the number for their desired option, they are redirected to another php file for further processing. In order for that script to know what option the key pressed corresponds to, we must first set this caller's order of options in a session variable.
