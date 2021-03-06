<?php

namespace Padam87\SearchBundle\Filter;

use Padam87\SearchBundle\Filter\ExprBuilder;
use Padam87\SearchBundle\Filter\ParameterBuilder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManager;

class EntityFilter extends AbstractFilter
{
    protected $entity;

    public function __construct(EntityManager $em, $entity, $alias)
    {
        parent::__construct($em);

        $this->entity = $entity;
        $this->alias = $alias;
    }

    public function toArray()
    {
        $fields = $this->_em->getClassMetadata(get_class($this->entity))->getFieldNames();
        $associations = $this->_em->getClassMetadata(get_class($this->entity))->getAssociationNames();

        foreach ($associations as $name) {
            if (!$this->_em->getClassMetadata(get_class($this->entity))->isCollectionValuedAssociation($name)) {
                $fields[] = $name;
            }
        }

        $filter = array();

        foreach ($fields as $field) {
            if($field == 'id') continue;

            $filter[$field] = $this->get($field);

            if (is_object($filter[$field]) && method_exists($filter[$field], 'getId')) {
                $filter[$field] = $filter[$field]->getId();
            }
        }

        return array_filter($filter, function ($item) {
            if($item === false) return true; // boolean field type
            if(empty($item)) return false;

            return true;
        });
    }

    public function toExpr()
    {
        $ExprBuilder = new ExprBuilder();

        $expressions = array();
        
        foreach ($this->toArray() as $name => $value) {
            $expressions[] = $ExprBuilder->getExpression($this->alias . '.' . $name, $value);
        }

        if (empty($expressions)) {
            return false;
        }

        $expr = new Expr\Andx($expressions);

        return $expr->__toString();
    }

    public function toParameters()
    {
        $ParamterBuilder = new ParameterBuilder();

        $parameters = array();

        foreach ($this->toArray() as $name => $value) {
            $parameter = $ParamterBuilder->getParameter($this->alias . '.' . $name, $value);

            if($parameter != NULL) $parameters[] = $parameter;
        }

        return $parameters;
    }

    public function get($field)
    {
        $getter = "get" . str_replace(" ", "", ucwords(str_replace("_", " ", $field)));

        return $this->entity->$getter();
    }
}
