# SIGen - Service Interface Generator

While working on the webservices api of an enterprise PHP application I've ran into the following problem:

If registering a class or class instance to PHP's SoapServer, all public methods will be exposed to the API. This also
affects Zend Framework's components which use PHP's reflection api.

I needed the ability to control the methods' visibility to the server component at runtime.

Imagine the following base class for handling different resources:

    abstract BaseResource {

        abstract public function getResourceHandler();

        /**
         * Deletes the resource with the given id
         *
         * @param int $id Resource id
         */
        public function delete($id) {
            $resource_handler = $this->getResourceHandler();
            if(!$resource_handler->mayBeDeleted()) {
                throw new \Exception('Resource of type '.$resource_handler->getTypeDescription().' may not be deleted';
            }
            if(!$this->getCurrentUser()->mayDelete($resource_handler) {
                throw new \Exception('You are not allowed to delete this resource';
            }

            return $resource_handler->deleteById($id);
        }
    }

A child class could look like this:

    class ImageResource {
        public function getResourceHandler() {
            return new ImageResourceHandler();
        }
    }

If registering ImageResource to the SoapServer, all public methods will be exposed, even if the resource type i. e.
disallows the deletion of this type's resources in general.

With SIGen one could rewrite the BaseResource as follows:

    abstract BaseResource {

        abstract public function getResourceHandler();

        /**
         * Deletes the ##$resource_handler->getResourceDescription()## with the given id
         *
         * @expose ##$resource_handler->mayBeDeleted() && $current_user->mayDelete($resource_handler)##
         * @param int $id ##$resource_handler->getResourceDescription()## id
         */
        public function delete($id) {
            return $this->getResourceHandler()->deleteById($id);
        }
    }

You now will not register the class itself to the soap server, but a class proxy ($proxy_object) which will be generated
on the fly:

    $service_object = new ImageResource();
    $interface_builder = new \SIGen\InterfaceBuilder(array('resource_handler' => $service_object->getResourceHandler(), 'current_user' => $service_object->getCurrentUser()));
    $proxy_object = $interface_builder->generateInstance($service_object);

$proxy_object will contain the method 'delete' only, if the expression `@expose ##<valid php expression>##` will return
true. Also the docblock's short description will change to a less generic one.

