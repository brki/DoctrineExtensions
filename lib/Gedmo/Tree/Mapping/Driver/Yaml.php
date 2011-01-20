<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidArgumentException;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Tree
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
 * @subpackage Yaml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';
    
    /**
     * List of types which are valid for timestamp
     * 
     * @var array
     */
    private $_validTypes = array(
        'integer',
        'smallint',
        'bigint'
    );
    
    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config)
    {
        if ($config) {
            $missingFields = array();
            if (!isset($config['parent'])) {
                $missingFields[] = 'ancestor';
            }
            if (!isset($config['left'])) {
                $missingFields[] = 'left';
            }
            if (!isset($config['right'])) {
                $missingFields[] = 'right';
            }
            if ($missingFields) {
                throw new InvalidArgumentException("Missing properties: " . implode(', ', $missingFields) . " in class - {$meta->name}");
            }
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $yaml = $this->_loadMappingFile($this->_findMappingFile($meta->name));
        $mapping = $yaml[$meta->name];
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treeLeft', $fieldMapping['gedmo'])) {
                        if (!$this->_isValidField($meta, $field)) {
                            throw new InvalidArgumentException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['left'] = $field;
                    } elseif (in_array('treeRight', $fieldMapping['gedmo'])) {
                        if (!$this->_isValidField($meta, $field)) {
                            throw new InvalidArgumentException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['right'] = $field;
                    } elseif (in_array('treeLevel', $fieldMapping['gedmo'])) {
                        if (!$this->_isValidField($meta, $field)) {
                            throw new InvalidArgumentException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['level'] = $field;
                    }
                }
            }
        }
        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo'])) {
                    if (in_array('treeParent', $relationMapping['gedmo'])) {
                        if ($relationMapping['targetEntity'] != $meta->name) {
                            throw new InvalidArgumentException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                }
            }
        }
    }
    
    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
    
    /**
     * Checks if $field type is valid
     * 
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField($meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->_validTypes);
    }
}