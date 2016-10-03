<?php

namespace lsm\libs;

use lsm\mappers\Mapper;
use lsm\libs\H;
use PDO;

class Validator {

    protected $_source;
    protected $_items;
    public $errors;
    public $checkToken = true;

    const MIN_PASSWD_REQUIREMENTS = 3;

    /**
     * Array of regular expressions for validation.
     * @var array
     */
    public static $regexp = array(
        'email' => ';(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\]);'
    );

    //
    // Constants for white-list validation
    //
    const NUMERIC = 1;
    const NUMERIC_INT = 2;
    const DATE = 3;
    const TIME = 4;

    public function __construct() {
        $this->_source = array();
        $this->errors = array();
    }

    /**
     * The $items array contain the field names and rules to validate
     * the values present in the $source array.
     *
     * @param array $source The source of the data (e.g. $_POST)
     * @param array $items Items to be validated (e.g. name) and its rules
     * @return bool
     */
    public function check( array $source, array $items = array() ) {
        // The source of the data (will generally be $_POST)
        $this->_source = $source;

        // The items to be checked
        $this->_items = $items;

        // test Token for CSFR protection
        if ( $this->checkToken ) {
            if ( ! H::checkToken( filter_var( $source[ 'token' ], FILTER_SANITIZE_SPECIAL_CHARS ) ) ) {
                $this->errors[] = "Não foi possível processar a requisição!";
                return false;
            }
        }

        foreach ( $this->_items as $item => $rules ) {
            // If the value to be checked is actually an array
            // (checkbox), call $this->checkArray()
            if ( isset( $rules[ 'array' ] ) && $rules[ 'array' ] ) {
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
        if ( ! isset( $this->_source[ $item ] ) ) {
            return;
        }

        $value = filter_var( $this->_source[ $item ], FILTER_SANITIZE_SPECIAL_CHARS );

        $fieldName = ( isset( $rules[ 'fieldName' ] ) ) ? $rules[ 'fieldName' ] : $item;

        // validation of data type
        $type = isset( $rules[ 'type' ] ) ? $rules[ 'type' ] : null;

        // validation to enforce that the value be one from a specific
        // set of values
        $valueIn = isset( $rules[ 'valueIn' ] ) ? $rules[ 'valueIn' ] : array();

        // other rules for data validation
        $validationRules = isset( $rules[ 'rules' ] ) ? explode( '|', $rules[ 'rules' ] ) : array();

        // Check if the field is mandatory and no data is set in the input array
        // (this generally means that no checkboxes were checked by the user)
        if ( in_array( 'required', $validationRules ) && empty( $value ) ) {
            $this->addError( ucfirst( $fieldName ) . ' é obrigatório.' );
            return;
        } else if ( $value ) {
            $this->validate( $value, $fieldName, $validationRules, $type, $valueIn );
        }
    }

    protected function checkArray( $item, array $rules ) {
        $fieldName = ( isset( $rules[ 'fieldName' ] ) ) ? $rules[ 'fieldName' ] : $item;

        // validation of data type
        $type = isset( $rules[ 'type' ] ) ? $rules[ 'type' ] : null;

        // validation to enforce that the value be one from a specific
        // set of values
        $valueIn = isset( $rules[ 'valueIn' ] ) ? $rules[ 'valueIn' ] : array();

        // other rules for data validation
        $validationRules = isset( $rules[ 'rules' ] ) ? explode( '|', $rules[ 'rules' ] ) : array();

        // Check if the field is mandatory and no data is set in the input array
        // (this generally means that no checkboxes were checked by the user)
        if ( in_array( 'required', $validationRules ) && ( ! isset( $this->_source[ $item ] ) ) ) {
            $this->addError( ucfirst( $fieldName ) . ' é obrigatório.' );
            return;
        }

        foreach ( $this->_source[ $item ] as $value ) {
            if ( $value ) {
                $value = filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );

                $this->validate( $value, $fieldName, $validationRules, $type, $valueIn );
            }
        }
    }

    /**
     * Performs the validation of data.
     *
     * @param $value
     * @param $fieldName
     * @param $rules
     * @param $type
     * @param $valueIn
     */
    protected function validate( $value, $fieldName, $rules, $type, $valueIn ) {
        // check data type
        if ( $type ) {
            if ( ( $type === self::NUMERIC_INT ) && ! ( is_numeric( $value ) && is_integer( (int) $value ) ) ) {
                $this->addError( ucfirst( $fieldName ) . " deve ser um número inteiro." );
            } else if ( $type === self::NUMERIC && ! is_numeric( $value ) ) {
                $this->addError( ucfirst( $fieldName ) . " deve ser um número." );
            } else if ( $type === self::DATE ) {
                $dt = \DateTime::createFromFormat( 'd/m/Y', $value );
                if ( $dt === false ) {
                    $this->addError( ucfirst( $fieldName ) . " deve ser uma data válida." );
                }
            } else if ( $type === self::TIME ) {
                $tm = \DateTime::createFromFormat( 'H:i', $value );
                if ( $tm === false ) {
                    $this->addError( ucfirst( $fieldName ) . " deve ser um horário válido." );
                }
            }
        }

        // check accepted array of values
        if ( count( $valueIn ) && !in_array( $value, $valueIn ) ) {
            $this->addError( "Valor inválido para {$fieldName}." );
        }

        foreach ( $rules as $rule )
        {
            if ( $rule == 'password' ) {
                $this->checkPassword( $value );
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
                            $this->addError( "Valor do campo " . ucfirst( $fieldName ) . " deve ser idêntico ao do campo {$this->_items[ $rule[ 1 ] ][ 'fieldName' ]}." );
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

                    case 'regex':
                        if ( ! preg_match( self::$regexp[ $rule[ 1 ] ], $value ) ) {
                            $this->addError( "Valor inválido para o campo {$fieldName}." );
                        }

                        break;
                }
            }
        }
    }

    /**
     * @param $errorMsg
     */
    protected function addError( $errorMsg ) {
        $this->errors[] = $errorMsg;
    }

    /**
     * @return string
     */
    public function getErrorsJson() {
        return json_encode( $this->errors );
    }

    /**
     * Checks if the password matches all password-complexity rules,
     * adding errors for the rules that are not matched
     *
     * @param $password
     */
    protected function checkPassword( $password ) {
        if ( ! $this->checkPassMin( $password ) ) {
            $this->addError( 'A Senha deve ter no mínimo 10 caracteres.' );
        } else if ( ! $this->checkPassMax( $password ) ) {
            $this->addError( 'A Senha deve ter no máximo 128 caracteres.' );
        }

        $arrRules = $this->checkPassComplexity( $password );

        $countRulesMet = count( array_filter( $arrRules, function ( $item ) {
            return $item;
        } ) );

        // The password must meet at least 3 complexity requirements
        if ( $countRulesMet < self::MIN_PASSWD_REQUIREMENTS ) {
            $errorMsg = 'A senha deve conter ao menos '
                . ( self::MIN_PASSWD_REQUIREMENTS - $countRulesMet ) . ' das características a seguir:';

            $errorMsg .= '<ul>';

            if ( ! $arrRules[ 'upper' ] ) {
                $errorMsg .= '<li>pelo menos uma letra maiúscula.</li>';
            }

            if ( ! $arrRules[ 'lower' ] ) {
                $errorMsg .= '<li>pelo menos uma letra minúscula.</li>';
            }

            if ( ! $arrRules[ 'digit' ] ) {
                $errorMsg .= '<li>pelo menos um dígito.</li>';
            }

            if ( ! $arrRules[ 'schar' ] ) {
                $errorMsg .= '<li>pelo menos um dos seguintes caracteres: '
                    . '\', ", @, #, $, %, &, *, (, ), -, _, +, =, ´, `, ^, ~, <, >, '
                    . '/, \\, ?, !, :, ;, |, [, ], {, }, ç, Ç, ¨</li>';
            }

            $errorMsg .= '</ul>';

            $this->addError( $errorMsg );
        }

        // Check if password contains three identical
        // chars in a a row
        if ( $arrRules[ 'trow' ] ) {
            $this->addError( 'A Senha não pode conter 3 caracteres iguais consecutivos.' );
        }
    }

    /**
     * @param $password
     * @return bool
     */
    protected function checkPassMin( $password ) {
        if ( strlen( $password ) < 10 ) {
            return false;
        }

        return true;
    }

    /**
     * @param $password
     * @return bool
     */
    protected function checkPassMax( $password ) {
        if ( strlen( $password ) > 128 ) {
            return false;
        }

        return true;
    }

    /**
     * Checks for password complexity.
     * Returns an array showing if the 4
     * rules of complexity are met, and
     * if 3 identical characters in a row
     * were found.
     *
     * @param $password
     * @return array
     */
    protected function checkPassComplexity( $password ) {
        // We'll count the amount of complexity rules met.
        // To be safe, the password must meet at least 3.
        $arrRules = [
            'upper' => false, // upper-case letter
            'lower' => false, // lower-case letter
            'digit' => false, // numerical value
            'schar' => false  // special characters
        ];

        // Check if there are three identical characters in a row:
        $arrRules[ 'trow' ] = false;

        for ( $i = 0, $count = 0; $i < strlen( $password ); $i++ ) {
            $char = $password[ $i ];

            if ( ctype_upper( $char ) ) {
                $arrRules[ 'upper' ] = true;
            } else if ( ctype_lower( $char ) ) {
                $arrRules[ 'lower' ] = true;
            } else if ( ctype_digit( $char ) ) {
                $arrRules[ 'digit' ] = true;
            } else if ( H::isSpecialChar( $char ) ) {
                $arrRules[ 'schar' ] = true;
            }

            // Test if it's third character in a row
            if ( $i != 0 ) {
                if ( $char == $password[ $i - 1 ] ) {
                    $count++;
                    if ( $count == 3 ) {
                        // Three identical chars in a row:
                        // set trow to true
                        $arrRules[ 'trow' ] = true;
                        $count = 0;
                    }
                }
            }
        }

        return $arrRules;
    }
}
