<?php
// +----------------------------------------------------------------------
// | Leaps Framework [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author XuTongle <xutongle@gmail.com>
// +----------------------------------------------------------------------
namespace Leaps\Di\Service;

use Leaps\Di\Exception;
use Leaps\Di\ContainerInterface;

/**
 * Phalcon\Di\Service\Builder
 *
 * This class builds instances based on complex definitions
 */
class Builder
{

	/**
	 * Resolves a constructor/call parameter
	 *
	 * @param Phalcon\DiInterface dependencyInjector
	 * @param int position
	 * @param array argument
	 * @return mixed
	 */
	private function _buildParameter(ContainerInterface $dependencyInjector,$position, $argument)
	{

		/**
		 * All the arguments must be an array
		 */
		if (!is_array($argument)) {
			throw new Exception("Argument at position " . $position . " must be an array");
		}

		/**
		 * All the arguments must have a type
		 */
		if (!isset($argument["type"])) {
			throw new Exception("Argument at position " . $position . " must have a type");
		}

		switch ($argument["type"]) {

			/**
			 * If the argument type is 'service', we obtain the service from the DI
			 */
			case "service":
				if (!isset($argument["name"])) {
					throw new Exception("Service 'name' is required in parameter on position " . $position);
				}
				if (!is_object($dependencyInjector)) {
					throw new Exception("The dependency injector container is not valid");
				}
				return $dependencyInjector->get($argument["name"]);
				/**
				 * If the argument type is 'parameter', we assign the value as it is
			 */
			case "parameter":
				if (!isset($argument["value"])) {
					throw new Exception("Service 'value' is required in parameter on position " . $position);
				}
				return $argument["value"];
				/**
				 * If the argument type is 'instance', we assign the value as it is
				 */
			case "instance":

				if (!isset($argument["className"])) {
					throw new Exception("Service 'className' is required in parameter on position " . $position);
				}
				if (!is_object($dependencyInjector)) {
					throw new Exception("The dependency injector container is not valid");
				}

				if (isset($argument["arguments"])) {
					/**
					 * Build the instance with arguments
					 */
					return $dependencyInjector->get($argument["className"], $argument["arguments"]);
				} else {
					/**
					 * The instance parameter does not have arguments for its constructor
					 */
					return $dependencyInjector->get($argument["className"]);
				}
			default:
				/**
				 * Unknown parameter type
				 */
				throw new Exception("Unknown service type in parameter on position " . $position);
		}
	}

	/**
	 * Resolves an array of parameters
	 *
	 * @param Phalcon\DiInterface dependencyInjector
	 * @param array arguments
	 * @return array
	 */
	private function _buildParameters(ContainerInterface $dependencyInjector, $arguments)
	{
		/**
		 * The arguments group must be an array of arrays
		 */
		if (!is_array($arguments)) {
			throw new Exception("Definition arguments must be an array");
		}

		$buildArguments = [];
		foreach ($arguments as $position=>$argument) {
			$buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
		}
		return $buildArguments;
	}

	/**
	 * Builds a service using a complex service definition
	 *
	 * @param Phalcon\DiInterface dependencyInjector
	 * @param array definition
	 * @param array parameters
	 * @return mixed
	 */
	public function build(ContainerInterface $dependencyInjector, $definition, $parameters = null)
	{


		/**
		 * The class name is required
		 */
		if (!isset($definition["className"])) {
			throw new Exception("Invalid service definition. Missing 'className' parameter");
		}
		if (is_array($parameters)) {
			/**
			 * Build the instance overriding the definition constructor parameters
			 */
			if (count($parameters)) {
				if (is_php_version("5.6")) {
					$reflection = new \ReflectionClass($definition["className"]);
					$instance = $reflection->newInstanceArgs($parameters);
				} else {
					$instance = create_instance_params($definition["className"], $parameters);
				}
			} else {
				if (is_php_version("5.6")) {
					$reflection = new \ReflectionClass($definition["className"]);
					$instance = $reflection->newInstance();
				} else {
					$instance = create_instance($definition["className"]);
				}
			}
		} else {
			/**
			 * Check if the argument has constructor arguments
			 */
			if(isset($definition["arguments"])) {
				/**
				 * Create the instance based on the parameters
				 */
				$instance = create_instance_params($definition["className"], $this->_buildParameters($dependencyInjector, $definition["arguments"]));

			} else {
				if (is_php_version("5.6")) {
					$reflection = new \ReflectionClass($definition["className"]);
					$instance = $reflection->newInstance();
				} else {
					$instance = create_instance($definition["className"]);
				}
			}
		}

		/**
		 * The definition has calls?
		 */
		if (isset($definition["calls"])) {

			if (!is_object($instance)) {
				throw new Exception("The definition has setter injection parameters but the constructor didn't return an instance");
			}
			if (!is_a($definition["calls"])) {
				throw new Exception("Setter injection parameters must be an array");
			}

			/**
			 * The method call has parameters
			 */
			foreach ($definition["calls"] as  $methodPosition=>$method){

				/**
				 * The call parameter must be an array of arrays
				 */
				if (!is_array($method)) {
					throw new Exception("Method call must be an array on position " . $methodPosition);
				}

				/**
				 * A param 'method' is required
				 */
				if (!isset($method["method"])) {
					throw new Exception("The method name is required on position " . $methodPosition);
				}
				/**
				 * Create the method call
				 */
				$methodCall = [$instance,  $method["method"]];
				if (isset($method["arguments"] )){

					if (!is_array($method["arguments"] )){
						throw new Exception("Call arguments must be an array " . methodPosition);
					}
					if (count($method["arguments"] )) {

						call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $method["arguments"] ));
						continue;
					}

				}
				call_user_func($methodCall);
			}

		}

		/**
		 * The definition has properties?
		 */
		if (isset($definition["properties"])) {

			if (!is_object($instance)) {
				throw new Exception("The definition has properties injection parameters but the constructor didn't return an instance");
			}

			if (!is_array($definition["properties"])) {
				throw new Exception("Setter injection parameters must be an array");
			}

			/**
			 * The method call has parameters
			 */
			foreach ($definition["properties"] as $propertyPosition=> $property) {

				/**
				 * The call parameter must be an array of arrays
				 */
				if (!is_array($property)) {
					throw new Exception("Property must be an array on position " . $propertyPosition);
				}

				/**
				 * A param 'name' is required
				 */
				if (!isset($property["name"])) {
					throw new Exception("The property name is required on position " . $propertyPosition);
				}

				/**
				 * A param 'value' is required
				 */
				if (!isset($property["value"])) {
					throw new Exception("The property value is required on position " . $propertyPosition);
				}
				/**
				 * Update the public property
				 */
				$instance->{$propertyName} = $this->_buildParameter($dependencyInjector, $property["name"], $property["value"]);
			}

		}

		return $instance;
	}
}