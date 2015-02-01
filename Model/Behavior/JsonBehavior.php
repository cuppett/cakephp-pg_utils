<?php
/**
 * PostgreSQL JSON Behavior
 *
 * This is a simple behavior allowing conversion of JSON database values to
 * a PHP array when retrieved by CakePHP. It will also compress arrays down
 * into a JSON database field when stored.
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

class JsonBehavior extends ModelBehavior
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

    public function afterFind(Model $model, $results, $primary = false)
    {

        // Loop over each result array
        for ($x = 0; $x < count($results); $x ++) {
            // Loop over all array fields
            foreach ($this->settings[$model->alias]['fields'] as $field) {
                if (isset($results[$x][$model->alias][$field]))
                    $results[$x][$model->alias][$field] = json_decode($results[$x][$model->alias][$field], true);
            }
        }
        return $results;
    }
}
