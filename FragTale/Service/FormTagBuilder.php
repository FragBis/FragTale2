<?php

namespace FragTale\Service;

use FragTale\DataCollection;
use FragTale\Implement\AbstractService;
use FragTale\Application\Model;

/**
 *
 * @author Fabrice Dant <fragtale.development@gmail.com>
 * @copyright 2024 FragTale 2 - Fabrice Dant
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-fr.txt CeCILL Licence 2.1 (French version)
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt CeCILL Licence 2.1 (English version)
 *         
 */
class FormTagBuilder extends AbstractService {
	/**
	 *
	 * @param array $attributes
	 * @return string
	 */
	protected function buildAttributesAsString(array $attributes): string {
		$parsedProperties = [ ];
		$disabled = ! empty ( $attributes ['disabled'] );
		unset ( $attributes ['disabled'] );
		$required = ! empty ( $attributes ['required'] );
		unset ( $attributes ['required'] );
		$readonly = ! empty ( $attributes ['readonly'] );
		unset ( $attributes ['readonly'] );
		$checked = ! empty ( $attributes ['checked'] );
		unset ( $attributes ['checked'] );
		foreach ( [ 
				'type',
				'id',
				'name',
				'value'
		] as $key ) {
			if (isset ( $attributes [$key] )) {
				$value = str_replace ( '"', '&quot;', ( string ) $attributes [$key] );
				$parsedProperties [] = "$key=\"$value\"";
				unset ( $attributes [$key] );
			}
		}
		foreach ( $attributes as $key => $value ) {
			$value = str_replace ( '"', '&quot;', ( string ) $value );
			$parsedProperties [] = "$key=\"$value\"";
		}
		$strProperties = implode ( ' ', $parsedProperties );
		if ($disabled)
			$strProperties .= ' disabled';
		if ($required)
			$strProperties .= ' required';
		if ($readonly)
			$strProperties .= ' readonly';
		if ($checked)
			$strProperties .= ' checked';
		return $strProperties;
	}

	/**
	 * Tag attributes contains all HTML tag properties such as id, name, class etc...
	 * Basically, you can set any attributes you want.
	 *
	 * @param array $attributes
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'type' => 'text', 'onkeyup' => "()=>{}", 'required' => true, 'disabled' => false, 'readonly' => false, 'value' => ''].
	 *        	By default, if type is not set, it will return an input type text.
	 * @return string
	 */
	public function input(array $attributes): string {
		$convertedTolowerKeys = [ ];
		foreach ( $attributes as $key => $value )
			$convertedTolowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		if (! isset ( $convertedTolowerKeys ['type'] ))
			$convertedTolowerKeys ['type'] = 'text';
		$strProperties = $this->buildAttributesAsString ( $convertedTolowerKeys );
		return "<input $strProperties />";
	}

	/**
	 * This returns only one checkbox
	 *
	 * @param array $inputAttributes
	 *        	HTML attributes for the input type checkbox
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'checked' => true, 'required' => true]
	 * @param string $text
	 *        	Displayed after the checkbox
	 * @param array $spanAttributes
	 *        	You might want to added style or CSS class to the text displayed after the checkbox
	 *        	Example: ['id' => 'spanId', 'class' => 'classname']
	 * @param array $labelAttributes
	 *        	HTML attributes for the label that triggers the checkbox. Attribute "for" does not need to be set as it corresponds to the input Id.
	 *        	Example: ['id' => 'labelId', 'class' => 'classname']
	 * @return string
	 */
	public function checkbox(array $inputAttributes, string $text, ?array $spanAttributes = null, ?array $labelAttributes = null): string {
		$inputConvertedTolowerKeys = $labelConvertedToLowerKeys = $spanConvertedToLowerKeys = [ ];
		foreach ( $inputAttributes as $key => $value )
			$inputConvertedTolowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		if ($labelAttributes) {
			foreach ( $labelAttributes as $key => $value )
				$labelConvertedToLowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		}
		if ($spanAttributes) {
			foreach ( $spanAttributes as $key => $value )
				$spanConvertedToLowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		}

		$inputId = isset ( $inputAttributes ['id'] ) ? $inputAttributes ['id'] : 'checkbox_' . substr ( md5 ( time () . rand () ), 0, 8 );
		$inputConvertedTolowerKeys ['id'] = $inputId;
		$inputConvertedTolowerKeys ['type'] = 'checkbox';
		$labelConvertedToLowerKeys ['for'] = $inputId;

		$strInputProperties = $this->buildAttributesAsString ( $inputConvertedTolowerKeys );
		$strLabelProperties = $this->buildAttributesAsString ( $labelConvertedToLowerKeys );
		$strSpanProperties = $this->buildAttributesAsString ( $spanConvertedToLowerKeys );
		return <<<HTML
			<label $strLabelProperties>
				<input $strInputProperties />
				<span $strSpanProperties>$text</span>
			</label>
		HTML;
	}

	/**
	 * This returns only one radio button!
	 *
	 * @param array $inputAttributes
	 *        	HTML attributes for the input type radio
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'checked' => true, 'required' => true]
	 * @param string $text
	 *        	Displayed after the radio button
	 * @param array $spanAttributes
	 *        	You might want to added style or CSS class to the text displayed after the radio button
	 *        	Example: ['id' => 'spanId', 'class' => 'classname']
	 * @param array $labelAttributes
	 *        	HTML attributes for the label that triggers the radio button. Attribute "for" does not need to be set as it corresponds to the input Id.
	 *        	Example: ['id' => 'labelId', 'class' => 'classname']
	 * @return string
	 */
	public function radio(array $inputAttributes, string $text, ?array $spanAttributes = null, ?array $labelAttributes = null): string {
		$inputConvertedTolowerKeys = $labelConvertedToLowerKeys = $spanConvertedToLowerKeys = [ ];
		foreach ( $inputAttributes as $key => $value )
			$inputConvertedTolowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		if ($labelAttributes) {
			foreach ( $labelAttributes as $key => $value )
				$labelConvertedToLowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		}
		if ($spanAttributes) {
			foreach ( $spanAttributes as $key => $value )
				$spanConvertedToLowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		}

		$inputId = isset ( $inputAttributes ['id'] ) ? $inputAttributes ['id'] : 'radio';
		$inputId .= '_' . substr ( md5 ( time () . rand () ), 0, 8 );
		$inputConvertedTolowerKeys ['id'] = $inputId;
		$inputConvertedTolowerKeys ['type'] = 'radio';
		$labelConvertedToLowerKeys ['for'] = $inputId;

		$strInputProperties = $this->buildAttributesAsString ( $inputConvertedTolowerKeys );
		$strLabelProperties = $this->buildAttributesAsString ( $labelConvertedToLowerKeys );
		$strSpanProperties = $this->buildAttributesAsString ( $spanConvertedToLowerKeys );
		return <<<HTML
			<label $strLabelProperties>
				<input $strInputProperties />
				<span $strSpanProperties>$text</span>
			</label>
		HTML;
	}

	/**
	 * Tag attributes contains all HTML tag properties such as id, name, class etc...
	 *
	 * @param array $attributes
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'onchange' => "()=>{}", 'required' => true].
	 * @param array $optionValues
	 *        	Example: ['' => '', 'option_value1' => 'option_text1']
	 * @param string $selectedValue
	 *        	Option matching this value will be selected
	 * @return string
	 */
	public function select(array $attributes, array $optionValues, ?string $selectedValue = null): string {
		$convertedTolowerKeys = [ ];
		foreach ( $attributes as $key => $value )
			$convertedTolowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		$strProperties = $this->buildAttributesAsString ( $convertedTolowerKeys );
		$htmlOutput = "<select $strProperties>";
		foreach ( $optionValues as $value => $text ) {
			$selected = ($selectedValue == $value) ? ' selected' : '';
			$value = htmlentities ( ( string ) $value );
			$text = htmlentities ( ( string ) $text );
			$htmlOutput .= "<option value=\"$value\"$selected>$text</option>";
		}
		return "$htmlOutput</select>";
	}

	/**
	 *
	 * @param array $attributes
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'onchange' => "()=>{}", 'required' => true].
	 * @return string
	 */
	public function textarea(array $attributes, ?string $content = ''): string {
		$convertedTolowerKeys = [ ];
		foreach ( $attributes as $key => $value )
			$convertedTolowerKeys [trim ( strtolower ( ( string ) $key ) )] = $value;
		$strProperties = $this->buildAttributesAsString ( $convertedTolowerKeys );
		$content = htmlentities ( ( string ) $content );
		return "<textarea $strProperties>$content</textarea>";
	}

	/**
	 * This form (input) builder auto return a selector in case of field is a foreign key.
	 * It returns only the form field (no label, no block).
	 *
	 * @param Model $Entity
	 *        	Instance of inherited Model
	 * @param string $columnName
	 *        	SQL column name
	 * @param string $defaultValue
	 *        	Selected or loaded value for this field
	 * @param array $extraInputAttributes
	 *        	Example: ['id' => 'inputId', 'class' => 'classname', 'onchange' => "()=>{}", 'required' => true].
	 * @return string
	 */
	public function autobuildEntityField(Model $Entity, string $columnName, ?string $defaultValue = null, ?array $extraInputAttributes = null): string {
		if (! $Entity->isEntityColumn ( $columnName ))
			return '<span class="message-error">' . sprintf ( dgettext ( 'core', 'Column "%1s" does not belong to entity "%2s"' ), $columnName, get_class ( $Entity ) ) . '</span>';

		if ($extraInputAttributes) {
			$tempProps = [ ];
			foreach ( $extraInputAttributes as $key => $value )
				$tempProps [trim ( strtolower ( ( string ) $key ) )] = $value;
			$extraInputAttributes = $tempProps;
		} else
			$extraInputAttributes = [ ];

		$columnDefinition = $Entity->getColumnDefinition ( $columnName );
		$tableName = $Entity->getTableName ();

		if (empty ( $extraInputAttributes ['id'] ))
			$extraInputAttributes ['id'] = "{$tableName}_$columnName";
		if (empty ( $extraInputAttributes ['name'] ))
			$extraInputAttributes ['name'] = "{$tableName}[$columnName]";

		$length = array_key_exists ( 'length', $columnDefinition ) ? (is_array ( $columnDefinition ['length'] ) ? ( int ) $columnDefinition ['length'] [0] : ( int ) $columnDefinition ['length']) : 0;
		if ($length && empty ( $extraInputAttributes ['maxlength'] ))
			$extraInputAttributes ['maxlength'] = $length;

		if (empty ( $columnDefinition ['nullable'] ))
			$extraInputAttributes ['required'] = true;

		if ($Entity->isPrimaryKey ( $columnName )) {
			$extraInputAttributes ['type'] = 'hidden';
			$extraInputAttributes ['value'] = $defaultValue;
			return $this->input ( $extraInputAttributes );
		} else {
			if ($Entity->isForeignKey ( $columnName )) {
				// Get foreign list to build a select tag with its options
				$ForeignEntity = clone $Entity->getForeignEntityFrom ( $columnName );
				$foreignPrimaryKeys = $ForeignEntity->getPrimaryKey ();
				if (! empty ( $foreignPrimaryKeys )) {
					$preferedDisplayedColumn = $ForeignEntity->getPreferedDisplayedColumn ();
					$foreignData = [ ];
					if (! $ForeignEntity->count ()) {
						$orderBy = $preferedDisplayedColumn ? [ 
								$preferedDisplayedColumn => 'ASC'
						] : [ 
								'1' => 'ASC'
						];
						$ForeignEntity->selectAs ( 'FK' )->orderBy ( $orderBy )->execute ();
					}
					foreach ( $ForeignEntity as $Data ) {
						if ($Data instanceof DataCollection) {
							if (count ( $foreignPrimaryKeys ) > 1) {
								$values = [ ];
								foreach ( $foreignPrimaryKeys as $fpk ) {
									$values [] = "$fpk:" . htmlentities ( ( string ) $Data->findByKey ( $fpk ) );
								}
								$value = implode ( ',', $values );
							} else
								$value = $Data->findByKey ( $foreignPrimaryKeys [0] );

							$text = '';
							if (! $preferedDisplayedColumn) {
								// By default, display primary key
								if (count ( $foreignPrimaryKeys ) > 1) {
									$texts = [ ];
									foreach ( $foreignPrimaryKeys as $fpk ) {
										$texts [] = $Data->findByKey ( $fpk );
									}
									$text = implode ( ',', $texts );
								} else
									$text = $Data->findByKey ( $foreignPrimaryKeys [0] );
							} else
								$text = $Data->findByKey ( $preferedDisplayedColumn );
							$foreignData [$value] = $text;
						}
					}
					return $this->select ( $extraInputAttributes, $foreignData, $defaultValue );
				}
			}
			if ($Entity->isInteger ( $columnName ) || $Entity->isFloat ( $columnName )) {
				$decLength = is_array ( $columnDefinition ['length'] ) ? ( int ) $columnDefinition ['length'] [1] : null;
				if (! $defaultValue && ! empty ( $columnDefinition ['default'] ) && empty ( $extraInputAttributes ['placeholder'] )) {
					$extraInputAttributes ['placeholder'] = $this->getSuperServices ()->getLocalizeService ()->toLocaleNumber ( $columnDefinition ['default'], $decLength );
				}
				if (empty ( $extraInputAttributes ['step'] )) {
					if ($Entity->isInteger ( $columnName )) {
						$extraInputAttributes ['step'] = 1;
					} else {
						$extraInputAttributes ['step'] = '0.' . str_pad ( '1', $columnDefinition ['length'] [1], '0', STR_PAD_LEFT );
					}
				}
				$extraInputAttributes ['type'] = 'number';
				$extraInputAttributes ['value'] = $defaultValue;
				return $this->input ( $extraInputAttributes );
			} else {
				switch (strtolower ( $columnDefinition ['type'] )) {
					case 'timestamp' :
					case 'datetime' :
						if (! empty ( $extraInputAttributes ['required'] )) {
							if (! $defaultValue && ! empty ( $columnDefinition ['default'] ) && stripos ( $columnDefinition ['default'], 'current' ) !== false)
								$defaultValue = date ( 'Y-m-d' ) . 'T' . date ( 'H:i' );
							elseif ($defaultValue)
								$defaultValue = date ( 'Y-m-d', strtotime ( $defaultValue ) ) . 'T' . date ( 'H:i', strtotime ( $defaultValue ) );
						}
						$extraInputAttributes ['type'] = 'datetime-local';
						$extraInputAttributes ['value'] = $defaultValue;
						return $this->input ( $extraInputAttributes );
					case 'date' :
						if (! empty ( $extraInputAttributes ['required'] )) {
							if (! $defaultValue && ! empty ( $columnDefinition ['default'] ) && stripos ( $columnDefinition ['default'], 'current' ) !== false)
								$defaultValue = date ( 'Y-m-d' );
							elseif ($defaultValue)
								$defaultValue = date ( 'Y-m-d', strtotime ( $defaultValue ) );
						}
						$extraInputAttributes ['type'] = 'date';
						$extraInputAttributes ['value'] = $defaultValue;
						return $this->input ( $extraInputAttributes );
					case 'time' :
						if (! empty ( $extraInputAttributes ['required'] )) {
							if (! $defaultValue && ! empty ( $columnDefinition ['default'] ) && stripos ( $columnDefinition ['default'], 'current' ) !== false)
								$defaultValue = date ( 'H:i' );
							elseif ($defaultValue)
								$defaultValue = date ( 'H:i', strtotime ( $defaultValue ) );
						}
						$extraInputAttributes ['type'] = 'time';
						$extraInputAttributes ['value'] = $defaultValue;
						return $this->input ( $extraInputAttributes );
					case 'bit' :
					case 'bool' :
					case 'boolean' :
						$defaultValue = ( int ) $defaultValue;

						if ($defaultValue)
							$extraInputAttributes ['checked'] = true;
						else
							unset ( $extraInputAttributes ['checked'] );
						$extraInputAttributes ['value'] = '1';
						$htmlOutput = $this->radio ( $extraInputAttributes, _ ( 'Yes' ) );

						if (! $defaultValue)
							$extraInputAttributes ['checked'] = true;
						else
							unset ( $extraInputAttributes ['checked'] );
						$extraInputAttributes ['value'] = '0';
						$htmlOutput .= $this->radio ( $extraInputAttributes, _ ( 'No' ) );

						return $htmlOutput;
					default :
						if (! $defaultValue && ! empty ( $columnDefinition ['default'] ) && empty ( $extraInputAttributes ['placeholder'] )) {
							$extraInputAttributes ['placeholder'] = htmlentities ( $columnDefinition ['default'] );
						}
						if ($length > 150 && empty ( $extraInputAttributes ['type'] ))
							return $this->textarea ( $extraInputAttributes, $defaultValue );
						else {
							$extraInputAttributes ['value'] = $defaultValue;
							return $this->input ( $extraInputAttributes );
						}
				}
			}
		}
		return '';
	}

	/**
	 * Just get label and form input (or select, textarea...), inline.
	 *
	 * @param Model $Entity
	 * @param string $columnName
	 * @param string $defaultValue
	 * @param array $extraInputAttributes
	 * @param array $labelAttributes
	 * @return string
	 */
	public function autobuildEntityFieldWithLabel(Model $Entity, string $columnName, ?string $defaultValue = null, ?array $extraInputAttributes = null, ?array $labelAttributes = null): string {
		if (! $Entity->isEntityColumn ( $columnName ))
			return '<div class="message-error">' . sprintf ( dgettext ( 'core', 'Column "%1s" does not belong to entity "%2s"' ), $columnName, get_class ( $Entity ) ) . '</div>';
		if ($Entity->isPrimaryKey ( $columnName ))
			return $this->autobuildEntityField ( $Entity, $columnName, $defaultValue, $extraInputAttributes );

		if ($extraInputAttributes) {
			$tempProps = [ ];
			foreach ( $extraInputAttributes as $key => $value )
				$tempProps [trim ( strtolower ( ( string ) $key ) )] = $value;
			$extraInputAttributes = $tempProps;
		} else
			$extraInputAttributes = [ ];

		$tableName = $Entity->getTableName ();
		$label = $Entity->getColumnLabel ( $columnName );
		$columnDefinition = $Entity->getColumnDefinition ( $columnName );
		if (empty ( $extraInputAttributes ['id'] ))
			$extraInputAttributes ['id'] = "{$tableName}_$columnName";
		$colIsBinary = ! empty ( $columnDefinition ['type'] ) && in_array ( strtolower ( $columnDefinition ['type'] ), [ 
				'bit',
				'bool',
				'boolean'
		] );

		if (! array_key_exists ( 'required', $extraInputAttributes ) && (! array_key_exists ( 'nullable', $columnDefinition ) || ! $columnDefinition ['nullable']))
			$extraInputAttributes ['required'] = true;

		if (! empty ( $extraInputAttributes ['required'] ))
			$label .= '*';

		$columnHtmlOutput = $this->autobuildEntityField ( $Entity, $columnName, $defaultValue, $extraInputAttributes );
		if (empty ( $labelAttributes ))
			$labelAttributes = [ ];
		if (! $colIsBinary)
			$labelAttributes ['for'] = $extraInputAttributes ['id'];
		$strLabelProperties = $this->buildAttributesAsString ( $labelAttributes );
		return $colIsBinary ? "<div $strLabelProperties>$label</div>$columnHtmlOutput" : "<label $strLabelProperties>$label</label>$columnHtmlOutput";
	}
}