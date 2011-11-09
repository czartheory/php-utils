<?php

namespace CzarTheory\Doctrine\DBAL\Schema;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * Table Diff
 *
 * @copyright Copyright (C) 2011 CzarTheory LLC.  All rights reserved.
 * @author  Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class EnhancedTableDiff extends TableDiff
{
    /**
     * All external table foreign keys impacted by a column change in this table
     *
     * @var array Elements are in the form (<table> => <constraint>)
     */
    public $impactedForeignKeys = array();
}