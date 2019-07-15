# ayvri_php_api
PHP library to interface with the Ayvri API

ayvri_lib.php is the core the application and has most but not all of the functionality that you will need. For more details check here:
 https://docs.ayvri.com/?shell#Sharing-Image
 
ayvri_controller_example.php shows a sample wrapper around the library to add your own business logic.  This is where you'd put your ayvri credentials.

avatar_api_example.php shows a rudimentary image server that adds the 'Access-Control-Allow-Origin: *' header which is critical since ayvri pulls in your images from another server, if you don't have that an error will occur and no images will serve.

ayvri_custom_loader_example.php shows how to do a local bulk load with some logic that is specific to my needs but can easily be modified for your purposes.
