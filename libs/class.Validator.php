<?php

class Validator {

    protected $_source;
    public $errors;
    public $checkToken = true;

    //
    // Constants for white-list validation
    //
    const NUMERIC = 1;
    const NUMERIC_INT = 2;

    public function __construct() {
        $this->_source = array();
        $this->errors = array();
    }

    /**
     * The $items array contain the field names and rules to validate
     * the values present in the $source array.
     *
     * @param array $source
     * @param array $items
     * @return bool
     */
    public function check( array $source, array $items = array() ) {
        $this->_source = $source;

        // test Token for CSFR protection
        if ( $this->checkToken ) {
            if ( ! H::checkToken( filter_var( $source[ 'token' ], FILTER_SANITIZE_SPECIAL_CHARS ) ) ) {
                $this->errors[] = "Não foi possível processar a requisição!";
                return false;
            }
        }

        foreach ( $items as $item => $rules ) {
            if ( is_array( $source[ $item ] ) ) {
                $this->checkArray( $item, $rules );
            } else {
                $this->checkValue( $item, $rules );
            }
        }

        if ( count( $this->errors ) ) {
            return false;
        }

        return true;
    }

    protected function checkValue( $item, array $rules ) {
        $value = filter_var( $this->_source[ $item ], FILTER_SANITIZE_SPECIAL_CHARS );

        $fieldName = ( isset( $rules[ 'fieldName' ] ) ) ? $rules[ 'fieldName' ] : $item;

        // validation of data type
        $type = isset( $rules[ 'type' ] ) ? $rules[ 'type' ] : null;

        // other rules for data validation
        $validationRules = isset( $rules[ 'rules' ] ) ? explode( '|', $rules[ 'rules' ] ) : array();

        $this->validate( $value, $fieldName, $validationRules, $type );
    }

    protected function checkArray( $item, array $rules ) {
        $fieldName = ( isset( $rules[ 'fieldName' ] ) ) ? $rules[ 'fieldName' ] : $item;

        // validation of data type
        $type = isset( $rules[ 'type' ] ) ? $rules[ 'type' ] : null;

        // other rules for data validation
        $validationRules = isset( $rules[ 'rules' ] ) ? explode( '|', $rules[ 'rules' ] ) : array();

        foreach ( $this->_source[ $item ] as $value ) {
            $value = filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );

            $this->validate( $value, $fieldName, $validationRules, $type );
        }
    }

    /**
     * Performs the validation of data.
     *
     * @param $value
     * @param $fieldName
     * @param $rules
     * @param $type
     */
    protected function validate( $value, $fieldName, $rules, $type ) {
        // check data type
        if ( $type ) {
            if ( ( $type === self::NUMERIC_INT ) && ! ( is_numeric( $value ) && is_integer( (int) $value ) ) ) {
                $this->addError( ucfirst( $fieldName ) . " deve ser um número inteiro." );
            } else if ( $type === self::NUMERIC && ! is_numeric( $value ) ) {
                $this->addError( ucfirst( $fieldName ) . " deve ser um número." );
            }
        }

        foreach ( $rules as $rule )
        {
            if ( $rule == 'required' && empty( $value ) ) {
                $this->addError( ucfirst( $fieldName ) . ' é obrigatório.' );
            } else {
                $rule = explode( ':', $rule );

                if ( ! isset( $rule[1] ) ) continue;

                switch ( $rule[ 0 ] ) {
                    // Checks if the value given has at most a certain number of characters.
                    // Example rule => "max:15".
                    case 'max':
                        if ( strlen( trim( $value ) ) > $rule[ 1 ] ) {
                            $this->addError( ucfirst( $fieldName ) . " deve ter no máximo {$rule[ 1 ]} caracteres." );
                        }

                        break;

                    case 'min':
                        // Checks if the value given has at least a certain number of characters.
                        // Example rule => "min:15".
                        if ( strlen( trim( $value ) ) < $rule[ 1 ] ) {
                            $this->addError( ucfirst( $fieldName ) . " deve ter no mínimo {$rule[ 1 ]} caracteres." );
                        }

                        break;

                    case 'matches':
                        // Checks if the value matches the value of another field in $source.
                        // Example rule => "matches:otherField:otherFieldLabel", where
                        // otherField is the name of the field whose value we have to match,
                        // and otherFieldLabel is the name by which this other field will be called
                        // in the error message.
                        $otherValue = filter_var( $this->_source[ $rule[ 1 ] ], FILTER_SANITIZE_SPECIAL_CHARS );

                        if ( $value != $otherValue ) {
                            $this->addError( "Valor do campo " . ucfirst( $fieldName ) . " deve ser idêntico ao do campo {$rule[ 2 ]}." );
                        }

                        break;

                    case 'unique':
                        // Checks if the value is unique (there wasn't any identical value(
                        // already stored in the Database.
                        // Example rule => "unique:dbTable:dbField", where dbTable is the
                        // name of the table in which we'll look for the value, and dbField
                        // is the name of field to be selected.
                        $mapper = new Mapper();
                        $arrCheck = $mapper->rawQuery(
                            "SELECT * FROM {$rule [ 1 ]} WHERE {$rule[ 2 ]} = ?",
                            array( $value ),
                            PDO::FETCH_ASSOC
                        );

                        if ( count( $arrCheck ) ) {
                            $this->addError( ucfirst( $fieldName ) . " já cadastrado." );
                        }

                        break;
                }
            }
        }
    }

    protected function addError( $errorMsg ) {
        $this->errors[] = $errorMsg;
    }

    public function getErrorsJson() {
        return json_encode( $this->errors );
    }
}
