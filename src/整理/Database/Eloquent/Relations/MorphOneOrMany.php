<?php
namespace Leaps\Database\Eloquent\Relations;

use Leaps\Database\Eloquent\Model;
use Leaps\Database\Eloquent\Builder;

abstract class MorphOneOrMany extends HasOneOrMany {

	/**
	 * The foreign key type for the relationship.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * The class name of the parent model.
	 *
	 * @var string
	 */
	protected $morphClass;

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  \Leaps\Database\Eloquent\Builder  $query
	 * @param  \Leaps\Database\Eloquent\Model  $parent
	 * @param  string  $type
	 * @param  string  $id
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $type, $id)
	{
		$this->morphType = $type;
		$this->morphClass = get_class($parent);
		parent::__construct($query, $parent, $id);
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		parent::addConstraints();
		$this->query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Leaps\Database\Eloquent\Builder  $query
	 * @return \Leaps\Database\Eloquent\Builder
	 */
	public function getRelationCountQuery(Builder $query)
	{
		$query = parent::getRelationCountQuery($query);
		return $query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		parent::addEagerConstraints($models);
		$this->query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Remove the original where clause set by the relationship.
	 *
	 * The remaining constraints on the query will be reset and returned.
	 *
	 * @return array
	 */
	public function getAndResetWheres()
	{
		$this->removeSecondWhereClause();
		return parent::getAndResetWheres();
	}

	/**
	 * Attach a model instance to the parent model.
	 *
	 * @param  \Leaps\Database\Eloquent\Model  $model
	 * @return \Leaps\Database\Eloquent\Model
	 */
	public function save(Model $model)
	{
		$model->setAttribute($this->getPlainMorphType(), $this->morphClass);
		return parent::save($model);
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param  array  $attributes
	 * @return \Leaps\Database\Eloquent\Model
	 */
	public function create(array $attributes)
	{
		$foreign = $this->getForeignAttributesForCreate();
		$attributes = array_merge($attributes, $foreign);
		$instance = $this->related->newInstance($attributes);
		$instance->save();
		return $instance;
	}

	/**
	 * Get the foreign ID and type for creating a related model.
	 *
	 * @return array
	 */
	protected function getForeignAttributesForCreate()
	{
		$foreign = array($this->getPlainForeignKey() => $this->parent->getKey());
		$foreign[last(explode('.', $this->morphType))] = $this->morphClass;
		return $foreign;
	}

	/**
	 * Get the foreign key "type" name.
	 *
	 * @return string
	 */
	public function getMorphType()
	{
		return $this->morphType;
	}

	/**
	 * Get the plain morph type name without the table.
	 *
	 * @return string
	 */
	public function getPlainMorphType()
	{
		return last(explode('.', $this->morphType));
	}

	/**
	 * Get the class name of the parent model.
	 *
	 * @return string
	 */
	public function getMorphClass()
	{
		return $this->morphClass;
	}
}