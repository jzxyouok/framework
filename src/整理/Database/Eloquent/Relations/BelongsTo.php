<?php
namespace Leaps\Database\Eloquent\Relations;
use LogicException;
use Leaps\Database\Eloquent\Model;
use Leaps\Database\Eloquent\Builder;
use Leaps\Database\Eloquent\Collection;
class BelongsTo extends Relation
{

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relation;

    /**
     * Create a new has many relationship instance.
     *
     * @param \Leaps\Database\Eloquent\Builder $query
     * @param \Leaps\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $relation)
    {
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;
        parent::__construct ( $query, $parent );
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first ();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $key = $this->related->getKeyName ();
        $table = $this->related->getTable ();
        $this->query->where ( $table . '.' . $key, '=', $this->parent->{$this->foreignKey} );
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * @param \Leaps\Database\Eloquent\Builder $query
     * @return \Leaps\Database\Eloquent\Builder
     */
    public function getRelationCountQuery(Builder $query)
    {
        throw new LogicException ( 'Has method invalid on "belongsTo" relations.' );
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $key = $this->related->getKeyName ();
        $key = $this->related->getTable () . '.' . $key;
        $this->query->whereIn ( $key, $this->getEagerModelKeys ( $models ) );
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param array $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = array ();
        foreach ( $models as $model ) {
            if ( ! is_null ( $value = $model->{$this->foreignKey} ) ) {
                $keys [] = $value;
            }
        }
        if ( count ( $keys ) == 0 ) {
            return array (
                    0
            );
        }
        return array_values ( array_unique ( $keys ) );
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array $models
     * @param string $relation
     * @return void
     */
    public function initRelation(array $models, $relation)
    {
        foreach ( $models as $model ) {
            $model->setRelation ( $relation, null );
        }
        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param \Leaps\Database\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;
        $dictionary = array ();
        foreach ( $results as $result ) {
            $dictionary [$result->getKey ()] = $result;
        }
        foreach ( $models as $model ) {
            if ( isset ( $dictionary [$model->$foreign] ) ) {
                $model->setRelation ( $relation, $dictionary [$model->$foreign] );
            }
        }
        return $models;
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param \Leaps\Database\Eloquent\Model $model
     * @return \Leaps\Database\Eloquent\Model
     */
    public function associate(Model $model)
    {
        $this->parent->setAttribute ( $this->foreignKey, $model->getKey () );
        return $this->parent->setRelation ( $this->relation, $model );
    }

    /**
     * Update the parent model on the relationship.
     *
     * @param array $attributes
     * @return mixed
     */
    public function update(array $attributes)
    {
        $instance = $this->getResults ();
        return $instance->fill ( $attributes )->save ();
    }

    /**
     * Get the foreign key of the relationship.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }
}