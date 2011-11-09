<?php

namespace CzarTheory\Doctrine\ORM;

use CzarTheory\Doctrine\DBAL\EnhancedConnection;
use Doctrine\ORM\EntityManager;
/**
 * @todo Add description of EnhancedEntityManager class.
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class EnhancedEntityManager extends EntityManager
{
	protected function __construct(EnhancedConnection $conn, Configuration $config, EventManager $eventManager)
	{
		parent::__construct($conn, $config, $eventManager);
	}
}
