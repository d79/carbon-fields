<?php

namespace Carbon_Fields\Value_Set;

use Carbon_Fields\Exception\Incorrect_Syntax_Exception;

/**
 * Class representing a field's value
 *
 * (raw) value set schema:
 * array(
 *     array(
 *         'value' => '',
 *         [property2] => '',
 *         ...
 *     ),
 *     ...
 * )
 */
class Value_Set {

	/**
	 * Value type which saves a single value
	 */
	const TYPE_SINGLE_VALUE = 1;

	/**
	 * Value type which saves multiple values with a single property
	 */
	const TYPE_MULTIPLE_VALUES = 2;

	/**
	 * Value type which saves a single value with multiple proerties
	 */
	const TYPE_MULTIPLE_PROPERTIES = 3;

	/**
	 * Value type which saves multiple values with multiple propertys
	 */
	const TYPE_VALUE_SET = 4;

	/**
	 * Default value property required for every value set
	 */
	const VALUE_PROPERTY = 'value';

	/**
	 * Value set type
	 * 
	 * @var integer static::TYPE_* constant
	 */
	protected $type = self::TYPE_SINGLE_VALUE;

	/**
	 * Array of valid value set types
	 *
	 * @var array
	 */
	protected $valid_types = array( self::TYPE_SINGLE_VALUE, self::TYPE_MULTIPLE_VALUES, self::TYPE_MULTIPLE_PROPERTIES, self::TYPE_VALUE_SET );

	/**
	 * Registered value set properties (properties) with their default value (when the property is missing in the passed raw_value_set)
	 *
	 * @var array
	 */
	protected $properties = array( self::VALUE_PROPERTY => '' );

	/**
	 * Data the value set represents
	 *
	 * @var array Nullable array
	 */
	protected $value_set = null;

	/**
	 * Value set constructor
	 *
	 * @param integer $type static::TYPE_* constant
	 * @param array $additional_properties
	 */
	public function __construct( $type = self::TYPE_SINGLE_VALUE, $additional_properties = array() ) {
		if ( ! in_array( $type, $this->valid_types ) ) {
			Incorrect_Syntax_Exception::raise( "Invalid type specified for Value_Set: $type" );
		}

		$this->type = $type;
		$this->properties = array_merge( $this->properties, $additional_properties );
	}

	/**
	 * Format a raw value set into one which guarantees that only (and all) registered properties are present
	 *
	 * @param array $raw_value_set
	 * @return array
	 */
	protected function get_formatted_value_set( $raw_value_set ) {
		$formatted_value_set = array();
		foreach ( $raw_value_set as $raw_value ) {
			$formatted_value = array();
			foreach ( $this->properties as $property => $default_value ) {
				$formatted_value[ $property ] = isset( $raw_value[ $property ] ) ? $raw_value[ $property ] : $default_value;
			}
			$formatted_value_set[] = $formatted_value;
		}
		return $formatted_value_set;
	}

	/**
	 * Return value set type
	 *
	 * @return int static::TYPE_* constant
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Return whether this value type requires a keepalive key
	 *
	 * @return boolean
	 */
	public function keepalive() {
		return ( $this->type !== static::TYPE_SINGLE_VALUE );
	}

	/**
	 * Return whether the data is empty
	 *
	 * @return boolean
	 */
	public function is_empty() {
		return empty( $this->value_set );
	}

	/**
	 * Return data formatted according to the value set $type
	 *
	 * @return mixed
	 */
	public function get() {
		if ( $this->value_set === null ) {
			return null;
		}
		$value = '';
		$value_property = static::VALUE_PROPERTY;

		switch ( $this->type ) {
			case static::TYPE_MULTIPLE_VALUES:
				$value = array_map( function( $set ) use ( $value_property ) {
					return $set[ $value_property ];
				}, $this->value_set );
				break;
			case static::TYPE_MULTIPLE_PROPERTIES:
				$value = array();
				if ( ! empty( $this->value_set ) ) {
					$value = $this->value_set[0];
				}
				break;
			case static::TYPE_VALUE_SET:
				$value = $this->value_set;
				break;

			case static::TYPE_SINGLE_VALUE:
			default:
				if ( ! empty( $this->value_set ) ) {
					$value = $this->value_set[0][ static::VALUE_PROPERTY ];
				}
				break;
		}

		return $value;
	}

	/**
	 * Return the full value set data regardless of type
	 * 
	 * @return array
	 */
	public function get_set() {
		return $this->value_set;
	}

	/**
	 * Check if an array is flat
	 *
	 * @param arrat $array
	 * @return boolean
	 */
	protected function is_flat_array( $array ) {
		$flat = true;
		foreach ( $array as $value ) {
			if ( is_array( $value ) ) {
				$flat = false;
				break;
			}
		}
		return $flat;
	}

	/**
	 * Convert a flat array to a raw value set
	 *
	 * @param array $value_array
	 * @return array
	 */
	protected function flat_array_to_raw_value_set( $value_array ) {
		$raw_value_set = array();

		$keyed = false;
		$keys = array_keys( $value_array );
		foreach ( $keys as $key ) {
			if ( is_string( $key ) ) {
				$keyed = true;
				break;
			}
		}

		if ( $keyed ) {
			$raw_value_set[] = $value_array;
		} else {
			foreach ( $value_array as $key => $value ) {
				if ( is_array( $value ) && isset( $value[ static::VALUE_PROPERTY ] ) ) {
					$raw_value_set[] = $value;
				} else {
					$raw_value_set[] = array(
						static::VALUE_PROPERTY => $value,
					);
				}
			}
		}

		return $raw_value_set;
	}

	/**
	 * Set the value set data
	 * Accepts: single value, array of values, array of key-values, array of arrays of key-values
	 *
	 * @param mixed $raw_value_set
	 */
	public function set( $raw_value_set ) {
		if ( $raw_value_set === null ) {
			$this->value_set = null;
			return;
		}

		if ( ! is_array( $raw_value_set ) ) {
			$raw_value_set = array( $raw_value_set );
		}

		if ( $this->is_flat_array( $raw_value_set ) ) {
			$raw_value_set = $this->flat_array_to_raw_value_set( $raw_value_set );
		}

		$this->value_set = $this->get_formatted_value_set( $raw_value_set );
	}
}