<?php

namespace Padam87\SearchBundle\Filter;

use Padam87\SearchBundle\Filter\ExprBuilder;
use Padam87\SearchBundle\Filter\ParameterBuilder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManager;

class CollectionFilter extends AbstractFilter
{
    protected $collection;

    public function __construct(EntityManager $em, $collection, $alias)
    {
        parent::__construct($em);

        $this->collection = $collection;
        $this->alias = $alias;
    }

    public function isMultipleLevel()
    {
        return true;
    }

    public function toArray()
    {
        $filter = array();

        $FilterFactory = new FilterFactory($this->_em);

        foreach ($this->collection->toArray() as $k => $entity) {
            $filter[$k] = $FilterFactory->create($entity, $this->alias)->toArray();
        }

        return array_filter($filter, function ($item) {
            if(empty($item)) return false;

            return true;
        });
    }

    public function toExpr()
    {
        $ExprBuilder = new ExprBuilder();

        $sets = array();

        foreach ($this->toArray() as $k => $set) {
            $expressions = array();
            foreach ($set as $name => $value) {
                $expressions[] = $ExprBuilder->getExpression($this->alias . '.' . $name, $value, $k);
            }
            $sets[] = new Expr\Andx($expressions);
        }

        if (empty($sets)) {
            return false;
        }

        $expr = new Expr\Orx($sets);

        return $expr->__toString();
    }

    public function toParameters()
    {
        $ParamterBuilder = new ParameterBuilder();

        $parameters = array();

        foreach ($this->toArray() as $k => $set) {
            foreach ($set as $name => $value) {
                $parameter = $ParamterBuilder->getParameter($this->alias . '.' . $name, $value, $k);

                if($parameter != NULL) $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    public function get($field)
    {
        return NULL;
    }
}
