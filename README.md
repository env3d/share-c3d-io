
# share.c3d.io
=============

This plugin powers share.c3d.io, which is a sharing infrastructure for
c3d.io family of products.

The plugin adds the following featuers to YOURLS:

 - All redirects will use javascript instead of Location header, because of the large url size
 - Adding the "Access-Control-Allow-Origin: *" header for CORS
 - Able to use Authorization Bearer as authentication method, must be a valid JWT
 - Parsing and validation of JWT

## Build and run

You must have Docker installed, and create a local image called share-c3d-io.  The ./build.sh file
will automate that.

Use the ./run.sh file to run, you will also need to specify all the appropriate environment variables
in a .env file.