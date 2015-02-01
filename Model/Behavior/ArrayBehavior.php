<?php
/**
 * PostgreSQL Array Behavior
 *
 * This is a simple behavior allowing conversion of array database values to
 * a PHP array when retrieved by CakePHP. It will also compress arrays down
 * into a database field when stored.
 *
 * Copyright (c) Stephen Cuppett (http://stephencuppett.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Stephen Cuppett (http://stephencuppett.com)
 * @package       Model.Behavior
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('ModelBehavior', 'Model');
App::uses('Model', 'Model');

class ArrayBehavior extends ModelBehavior
{

    public function setup(Model $model, $settings = array())
    {
        if (! isset($this->settings[$model->alias])) {
            $this->settings[$model->alias] = array(
                'fields' => array()
            );
        }
        $this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);
    }

    public function beforeSave(Model $model, $options = array())
    {

        // Loop over all array fields
        foreach ($this->settings[$model->alias]['fields'] as $field) {
            if (isset($model->data[$model->alias][$field]) && is_array($model->data[$model->alias][$field])) {
                // Multiple values selected
                $model->data[$model->alias][$field] = '{' . implode(',', $model->data[$model->alias][$field]) . '}';
            } else
                if (isset($model->data[$model->alias][$field])) {
                    // One or none values selected
                    $model->data[$model->alias][$field] = '{' . $model->data[$model->alias][$field] . '}';
                }
        }
        return true;
    }

    public function afterFind(Model $model, $results, $primary = false)
    {

        // Loop over each result array
        for ($x = 0; $x < count($results); $x ++) {
            // Loop over all array fields
            foreach ($this->settings[$model->alias]['fields'] as $field) {
                if (isset($results[$x][$model->alias][$field])) {
                    // Strip out the PostgreSQL special characters
                    $results[$x][$model->alias][$field] = trim(str_replace(array(
                        '{',
                        '}',
                        '"'
                    ), '', $results[$x][$model->alias][$field]));
                    if (strlen($results[$x][$model->alias][$field]) > 0)
                        // Blow up the array of values
                        $results[$x][$model->alias][$field] = explode(',', $results[$x][$model->alias][$field]);
                    else
                        $results[$x][$model->alias][$field] = array();
                }
            }
        }
        return $results;
    }
}