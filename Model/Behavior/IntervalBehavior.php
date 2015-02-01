<?php
/**
 * PostgreSQL Interval Behavior
 *
 * This is a simple behavior allowing conversion of interval values into the appropriate
 * ISO8601 format for storage into the database.
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

class IntervalBehavior extends ModelBehavior
{

    const INTERVAL_ISO8601 = 'P%yY%mM%dDT%hH%iM%sS';

    public function setup(Model $model, $settings = array())
    {
        if (! isset($this->settings[$model->alias])) {
            $this->settings[$model->alias] = array(
                'fields' => array()
            );
        }
        $this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);

        // Let's add the validator to the model.
        foreach ($this->settings[$model->alias]['fields'] as $field) {
            $model->validator()->add($field, 'valid_interval', array(
                'rule' => 'validateInterval',
                'message' => 'Invalid interval'
            ));
        }
    }

    /**
     *
     * @see ModelBehavior::beforeValidate()
     */
    function beforeValidate(Model $model, $options = array())
    {
        foreach ($this->settings[$model->alias]['fields'] as $field) {
            // If the user wants to input "1 week", so be it. Still have to convert it to ISO8601 format.
            try {
                if (isset($model->data[$model->alias][$field]) && strtotime($model->data[$model->alias][$field])) {
                    $dateInterval = DateInterval::createFromDateString($model->data[$model->alias][$field]);
                    $model->data[$model->alias][$field] = $dateInterval->format(PgIntervalBehavior::INTERVAL_ISO8601);
                }
            } catch (\Exception $e) {}
        }
        return true;
    }

    function beforeSave(Model $model, $options = array())
    {
        foreach ($this->settings[$model->alias]['fields'] as $field) {
            // If the user wants to input "1 week", so be it. Still have to convert it to ISO8601 format.
            try {
                if (isset($model->data[$model->alias][$field])) {
                    $model->data[$model->alias][$field] = trim($model->data[$model->alias][$field]);
                    if ($model->data[$model->alias][$field] == '')
                        $model->data[$model->alias][$field] = null;
                    else
                        $model->data[$model->alias][$field] = strtoupper($model->data[$model->alias][$field]);
                }
            } catch (\Exception $e) {}
        }
        return true;
    }

    /**
     * This validator ensures we have a valid format for the PostgreSQL save.
     *
     * @param array $check
     *            Contains the name of the field & the value.
     * @return boolean True when valid, false if there is a problem.
     */
    function validateInterval(Model $model, $check)
    {
        $value = array_values($check);
        $value = trim($value[0]);
        if ($value != '') {
            $value = strtoupper($value);
            if (strpos($value, 'P') != 0)
                $value = 'P' . $value;
            try {
                $dateInterval = new DateInterval($value);
            } catch (\Exception $e) {
                $this->log("Invalid interval entered: " + $value);
                return false;
            }
        }

        return true;
    }
}
