<?php
/**
 * PostgreSQL Search Behavior
 *
 * This behavior allows searching based on a query string. Individual fields and levels can be
 * identified via configuration.
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
App::uses('CakeTsQueryBuilder', 'PgUtils.Lib');

class SearchBehavior extends ModelBehavior
{

    private $regconfig = 'english';

    /**
     * Configure the search engine.
     * Identifies the column containing the ts_vector
     * as well as what, if any, weights are associated with individual fields.
     *
     * Example:
     *
     * $this->Behaviors->load(
     * 'PgUtils.Search', array(
     * 'column' => 'searchable_text',
     * 'weights' => array(
     * 'name' => 'A',
     * 'tags' => 'B',
     * 'description' => 'D'
     * )
     * )
     * );
     *
     * @see ModelBehavior::setup()
     */
    public function setup(Model $model, $settings = array())
    {
        if (! isset($this->settings[$model->alias])) {
            $this->settings[$model->alias] = array(
                'weights' => array(),
                'column' => null
            );
        }
        $this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);

        if (Configure::read('debug') > 1)
            $this->log('Search settings: ' + var_export($this->settings, true), LOG_DEBUG);
    }

    /**
     * Behaves similar to a find('all') style query.
     * Additional values processed
     * from the $options array:
     *
     * headline = Array of fields to highlight with matches from the search. Virtual fields will
     * be present for the query. They will be in the form name_headline where name is the original
     * field and the new field has the extra markup.
     * weights = Array of weights to use when ranking various levels
     * fn_rank = Name of ranking function to use (default 'ts_rank')
     *
     * Example:
     *
     * $Book->search("title:Wind", array('headline' => array('title'), weights => array('A' => 1.25)));
     *
     * @param Model $model
     *            The model currently being operated on
     * @param string $text_query
     *            An english query. Basic support provided for and, or, - operator as well
     *            as some weighted properties.
     * @param array $options
     *            Available options for find('all') plus those listed above.
     * @return Results of query expression, null for failure
     */
    public function search(Model $model, $text_query, $options = array())
    {
        try {
            $ts_query = new CakeTsQueryBuilder($text_query, $this->settings[$model->alias]['weights']);

            if ($this->settings[$model->alias]['column'] != null) {

                $query = 'to_tsquery(\'' . $this->regconfig . '\', \'' . pg_escape_string($ts_query->getQuery()) . '\')';

                // Generate additional WHERE clause
                if (! isset($options['conditions']))
                    $options['conditions'] = array();
                $options['conditions'][] = $model->alias . '.' . $this->settings[$model->alias]['column'] . ' @@@ to_tsquery(\'' . $this->regconfig . '\', \'' . pg_escape_string($ts_query->getQuery()) . '\')';

                // Create highlight columns desired
                if (isset($options['headline'])) {
                    foreach ($options['headline'] as $highlight) {
                        $model->virtualFields[$highlight . '_headline'] = 'ts_headline(' . $model->alias . '.' . $highlight . ',' . $query . ', \'HighlightAll=TRUE\')';
                    }
                    // If the user is restricting the fields, include matching, generated headlines
                    if (isset($options['fields']))
                        foreach ($options['fields'] as $field) {
                            if (in_array($field, $options['headline']))
                                $options['fields'][] = $field . '_headline';
                        }
                }

                // Merge user provided weights with default weights.
                $weights = array(
                    'D' => 0.1,
                    'C' => 0.2,
                    'B' => 0.4,
                    'A' => 1.0
                );
                if (isset($options['weights'])) {
                    $weights = array_merge($weights, $options['weights']);
                    $weights = array_intersect_key($weights, array(
                        'A' => null,
                        'B' => null,
                        'C' => null,
                        'D' => null
                    ));
                    ksort($weights, SORT_STRING);
                    $weights = array_reverse($weights);
                }

                // Identify correct/preferred rank function
                $fn_rank = 'ts_rank';
                if (isset($options['fn_rank']))
                    $fn_rank = $options['fn_rank'];

                    // Add ranking virtual field
                $model->virtualFields[$this->settings[$model->alias]['column'] . '_rank'] = 'ts_rank(\'{' . implode(',', $weights) . '}\', ' . $this->settings[$model->alias]['column'] . ',' . $query . ')';

                if (! isset($options['order']))
                    $options['order'] = array();
                    // Sort by rank as the first argument
                array_unshift($options['order'], $model->alias . '.' . $this->settings[$model->alias]['column'] . '_rank DESC');
            }

            return $model->find('all', $options);
        } catch (\Exception $e) {
            $this->log('Failure searching. ' . $e->__toString());
        } finally {
            if ($this->settings[$model->alias]['column'] != null)
                unset($model->virtualFields[$this->settings[$model->alias]['column'] . '_rank']);
            if (isset($options['headline']))
                foreach ($options['headline'] as $highlight)
                    unset($model->virtualFields[$highlight . '_headline']);
        }

        return array();
    }
}
