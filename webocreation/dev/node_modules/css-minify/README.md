# css-minify

### a css-compression command line tool

## Install:

`yarn global add css-minify`

or

`npm install css-minify -g`

## Usage:

If you want to deal with a single css file,you can do it like this:

`css-minify -f filename`

or 

`css-minify --file filename`

**Warning:**

Be sure that filename must be ended with ".css"

However,if you have too many css files to deal with,you can do it like this:

 `css-minify -d sourcedir`

or 

`css-minify --dir sourcedir`

**Tips:**

All css source files are in the sourcedir directory

At last,you will see the minified css file,which is ended with `.min.css`,in `current directory /css-dist`.

 