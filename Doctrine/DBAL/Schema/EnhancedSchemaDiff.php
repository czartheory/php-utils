<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace CzarTheory\Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\SchemaDiff;

/**
 * Schema Diff
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class EnhancedSchemaDiff extends SchemaDiff
{
    /**
     * @param AbstractPlatform $platform
     * @param bool $saveMode
     * @return array
     */
    protected function _toSql(AbstractPlatform $platform, $saveMode = false)
    {
        $sql = array();

        if ($platform->supportsForeignKeyConstraints() && $saveMode == false) {
            foreach ($this->orphanedForeignKeys AS $orphanedForeignKey) {
                $sql[] = $platform->getDropForeignKeySQL($orphanedForeignKey, $orphanedForeignKey->getLocalTableName());
            }
        }

        if ($platform->supportsSequences() == true) {
            foreach ($this->changedSequences AS $sequence) {
                $sql[] = $platform->getAlterSequenceSQL($sequence);
            }

            if ($saveMode === false) {
                foreach ($this->removedSequences AS $sequence) {
                    $sql[] = $platform->getDropSequenceSQL($sequence);
                }
            }

            foreach ($this->newSequences AS $sequence) {
                $sql[] = $platform->getCreateSequenceSQL($sequence);
            }
        }

        $foreignKeySql = array();
        foreach ($this->newTables AS $table) {
            $sql = array_merge(
                $sql,
                $platform->getCreateTableSQL($table, AbstractPlatform::CREATE_INDEXES)
            );

            if ($platform->supportsForeignKeyConstraints()) {
                foreach ($table->getForeignKeys() AS $foreignKey) {
                    $foreignKeySql[] = $platform->getCreateForeignKeySQL($foreignKey, $table);
                }
            }
        }
        $sql = array_merge($sql, $foreignKeySql);

        if ($saveMode === false) {
            foreach ($this->removedTables AS $table) {
                $sql[] = $platform->getDropTableSQL($table);
            }
        }

		$preSql = array();
		$postSql = array();
        foreach ($this->changedTables AS $tableDiff) {
			foreach ($tableDiff->impactedForeignKeys as $tableName => $constraint)
			{
				$preSql = array_merge($preSql, array($platform->getDropForeignKeySQL($constraint, $tableName)));
				$postSql = array_merge($postSql, array($platform->getCreateForeignKeySQL($constraint, $tableName)));
			}

            $sql = array_merge($sql, $platform->getAlterTableSQL($tableDiff));
        }

        return array_merge($preSql, $sql, $postSql);
    }
}